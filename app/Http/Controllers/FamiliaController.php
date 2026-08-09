<?php

namespace App\Http\Controllers;

use App\Events\Familia\CanvasCleared;
use App\Events\Familia\ChatPosted;
use App\Events\Familia\DrawStroke;
use App\Events\Familia\RoomUpdated;
use App\Models\FamilyMember;
use App\Models\FamilyRoom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class FamiliaController extends Controller
{
    private const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    private const GAMES = ['pictionary', 'trivia', 'tuttifrutti', 'hangman', 'memoria'];

    // ---------------------------------------------------------------- páginas
    public function index()
    {
        return Inertia::render('Familia/Index', [
            'seo' => [
                'title' => 'FIFARDOS Minijuegos — Jugá en vivo desde cualquier lugar',
                'description' => 'Creá una sala, compartí el código y jugá Dibuja y Adivina, Trivia o Tutti Frutti '
                    . 'con hasta 10 participantes desde cualquier parte del mundo, en tiempo real y gratis.',
                'type' => 'website',
            ],
        ]);
    }

    public function room(string $code)
    {
        $room = $this->findRoom($code);
        if (! $room) {
            return redirect()->route('familia.index')->with('error', 'Esa sala no existe.');
        }

        return Inertia::render('Familia/Room', [
            'code' => $room->code,
            'room' => $room->publicSnapshot(),
            'config' => [
                'max_families' => (int) config('familia.max_families'),
                'min_families' => (int) config('familia.min_families'),
            ],
        ]);
    }

    // ---------------------------------------------------------------- salas
    public function create(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:24',
            'token' => 'required|string|max:64',
            'game' => 'nullable|in:' . implode(',', self::GAMES),
        ]);

        $room = FamilyRoom::create([
            'code' => $this->uniqueCode(),
            'game' => $data['game'] ?? 'pictionary',
            'status' => 'lobby',
            'host_token' => $data['token'],
        ]);

        FamilyMember::create([
            'family_room_id' => $room->id,
            'name' => trim($data['name']),
            'token' => $data['token'],
            'slot' => 1,
            'is_host' => true,
            'last_seen_at' => now(),
        ]);

        return response()->json(['code' => $room->code]);
    }

    public function join(Request $request, string $code)
    {
        $data = $request->validate([
            'name' => 'required|string|max:24',
            'token' => 'required|string|max:64',
        ]);

        return DB::transaction(function () use ($data, $code) {
            $room = $this->findRoom($code, lock: true);
            if (! $room) {
                return response()->json(['message' => 'Esa sala no existe.'], 404);
            }

            $existing = $room->members()->where('token', $data['token'])->first();
            if ($existing) {
                $existing->update(['name' => trim($data['name']), 'last_seen_at' => now()]);
                $this->emit(new RoomUpdated($room->fresh('members')));
                return response()->json(['code' => $room->code, 'slot' => $existing->slot]);
            }

            if ($room->status !== 'lobby') {
                return response()->json(['message' => 'La partida ya empezó.'], 422);
            }
            if ($room->members()->count() >= config('familia.max_families')) {
                return response()->json(['message' => 'La sala está llena (máximo ' . config('familia.max_families') . ' participantes).'], 422);
            }

            $usedSlots = $room->members()->pluck('slot')->all();
            $slot = collect(range(1, config('familia.max_families')))->first(fn ($s) => ! in_array($s, $usedSlots));

            FamilyMember::create([
                'family_room_id' => $room->id,
                'name' => trim($data['name']),
                'token' => $data['token'],
                'slot' => $slot,
                'is_host' => false,
                'last_seen_at' => now(),
            ]);

            $room->load('members');
            $this->emit(new RoomUpdated($room));
            $this->emit(new ChatPosted($room->code, 'system', "{$data['name']} se unió a la sala."));

            return response()->json(['code' => $room->code, 'slot' => $slot]);
        });
    }

    public function setGame(Request $request, string $code)
    {
        $data = $request->validate([
            'token' => 'required|string|max:64',
            'game' => 'required|in:' . implode(',', self::GAMES),
        ]);

        return DB::transaction(function () use ($data, $code) {
            $room = $this->findRoom($code, lock: true);
            if (! $room) {
                return response()->json(['message' => 'not found'], 404);
            }
            $me = $this->getMember($room, $data['token']);
            if (! $me || ! $me->is_host) {
                return response()->json(['message' => 'solo el anfitrión'], 403);
            }
            if ($room->status === 'playing') {
                return response()->json(['message' => 'la partida está en curso'], 422);
            }
            $room->update(['game' => $data['game']]);
            $this->emit(new RoomUpdated($room->fresh('members')));

            return response()->json(['ok' => true]);
        });
    }

    // ---------------------------------------------------------------- presencia / identidad
    public function hello(Request $request, string $code)
    {
        $request->validate(['token' => 'required|string|max:64']);
        $room = $this->findRoom($code);
        if (! $room) {
            return response()->json(['message' => 'not found'], 404);
        }
        $me = $this->getMember($room, $request->token);
        if ($me) {
            $wasOffline = ! $me->last_seen_at || $me->last_seen_at->lt(now()->subSeconds(30));
            $me->update(['last_seen_at' => now()]);
            if ($wasOffline) {
                $this->emit(new RoomUpdated($room->fresh('members')));
            }
        }

        return response()->json([
            'room' => $room->publicSnapshot(),
            'me' => $this->mePayload($room, $me),
        ]);
    }

    public function me(Request $request, string $code)
    {
        $room = $this->findRoom($code);
        if (! $room) {
            return response()->json(['message' => 'not found'], 404);
        }
        $me = $this->getMember($room, $request->query('token'));

        return response()->json([
            'room' => $room->publicSnapshot(),
            'me' => $this->mePayload($room, $me),
        ]);
    }

    public function word(Request $request, string $code)
    {
        $room = $this->findRoom($code);
        if (! $room || $room->status !== 'playing' || $room->game !== 'pictionary') {
            return response()->json(['message' => 'no word'], 404);
        }
        $me = $this->getMember($room, $request->query('token'));
        if (! $me || $me->id !== $room->drawer_member_id) {
            return response()->json(['message' => 'forbidden'], 403);
        }

        return response()->json(['word' => $room->word]);
    }

    // ---------------------------------------------------------------- inicio / ciclo
    public function start(Request $request, string $code)
    {
        $data = $request->validate([
            'token' => 'required|string|max:64',
            'games' => 'nullable|array|min:1|max:3',
            'games.*' => 'in:' . implode(',', self::GAMES),
        ]);

        return DB::transaction(function () use ($data, $code) {
            $room = $this->findRoom($code, lock: true);
            if (! $room) {
                return response()->json(['message' => 'not found'], 404);
            }
            $me = $this->getMember($room, $data['token']);
            if (! $me || ! $me->is_host) {
                return response()->json(['message' => 'solo el anfitrión puede empezar'], 403);
            }
            if ($room->members()->count() < config('familia.min_families')) {
                return response()->json(['message' => 'Se necesitan al menos 2 participantes.'], 422);
            }

            // Tanda elegida (1 a 3 juegos, en orden); respaldo: la guardada o el juego actual.
            $playlist = ! empty($data['games'])
                ? array_values(array_unique($data['games']))
                : ($room->playlist ?: [$room->game]);

            $room->update(['playlist' => $playlist, 'playlist_pos' => 0, 'game' => $playlist[0]]);
            $this->beginGame($room, resetScores: true);

            return response()->json(['ok' => true]);
        });
    }

    public function setPlaylist(Request $request, string $code)
    {
        $data = $request->validate([
            'token' => 'required|string|max:64',
            'games' => 'present|array|max:3',
            'games.*' => 'in:' . implode(',', self::GAMES),
        ]);

        return DB::transaction(function () use ($data, $code) {
            $room = $this->findRoom($code, lock: true);
            if (! $room) {
                return response()->json(['message' => 'not found'], 404);
            }
            $me = $this->getMember($room, $data['token']);
            if (! $me || ! $me->is_host) {
                return response()->json(['message' => 'solo el anfitrión'], 403);
            }
            if ($room->status === 'playing') {
                return response()->json(['message' => 'la partida está en curso'], 422);
            }
            $games = array_values(array_unique($data['games']));
            $room->update(['playlist' => $games, 'game' => $games[0] ?? $room->game]);
            $this->emit(new RoomUpdated($room->fresh('members')));

            return response()->json(['ok' => true]);
        });
    }

    private function beginGame(FamilyRoom $room, bool $resetScores): void
    {
        $count = $room->members()->count();
        $total = match ($room->game) {
            'trivia' => min((int) config('familia.trivia.rounds'), count(config('familia.trivia.questions'))),
            'tuttifrutti' => (int) config('familia.tuttifrutti.rounds'),
            'hangman' => (int) config('familia.hangman.rounds'),
            'memoria' => (int) config('familia.memoria.rounds'),
            default => $count * (int) config('familia.pictionary.rounds_per_family'),
        };
        // game_score se reinicia cada partida; score (total de la tanda) solo al empezar la tanda.
        $room->members()->update($resetScores ? ['score' => 0, 'game_score' => 0] : ['game_score' => 0]);
        $room->update([
            'status' => 'playing',
            'round' => 0,
            'total_rounds' => $total,
            'state' => ['used' => []],
        ]);
        $this->startNextRound($room);
    }

    public function timeout(Request $request, string $code)
    {
        return DB::transaction(function () use ($code) {
            $room = $this->findRoom($code, lock: true);
            if (! $room || $room->status !== 'playing') {
                return response()->json(['ok' => false]);
            }
            // Tolerancia de 1s: el cliente puede pedir el avance un pelín antes por
            // el redondeo del contador. Solo rechazamos si aún falta más de 1 segundo.
            if ($room->round_ends_at && $room->round_ends_at->gt(now()->addSecond())) {
                return response()->json(['ok' => false]);
            }
            $phase = $room->state['phase'] ?? null;
            if ($phase === 'play') {
                $this->finishPlay($room);
            } elseif ($phase === 'validate') {
                $this->finishValidate($room);
            } elseif ($phase === 'reveal') {
                $this->advance($room);
            } elseif ($phase === 'gameover') {
                $this->advanceToNextGame($room);
            }

            return response()->json(['ok' => true]);
        });
    }

    // ---------------------------------------------------------------- Pictionary
    public function stroke(Request $request, string $code)
    {
        $data = $request->validate([
            'token' => 'required|string|max:64',
            'points' => 'required|array|max:300',
            'points.*.x' => 'required|numeric',
            'points.*.y' => 'required|numeric',
            'color' => 'nullable|string|max:12',
            'size' => 'nullable|numeric',
            'begin' => 'nullable|boolean',
        ]);
        $room = $this->findRoom($code);
        if (! $room || $room->status !== 'playing' || $room->game !== 'pictionary') {
            return response()->json(['message' => 'no'], 409);
        }
        $me = $this->getMember($room, $data['token']);
        if (! $me || $me->id !== $room->drawer_member_id) {
            return response()->json(['message' => 'forbidden'], 403);
        }

        $this->emit(new DrawStroke(
            $room->code, $data['token'], $data['points'],
            $data['color'] ?? '#111111', (float) ($data['size'] ?? 4), (bool) ($data['begin'] ?? false),
        ));

        return response()->json(['ok' => true]);
    }

    public function clearCanvas(Request $request, string $code)
    {
        $request->validate(['token' => 'required|string|max:64']);
        $room = $this->findRoom($code);
        if (! $room) {
            return response()->json(['message' => 'not found'], 404);
        }
        $me = $this->getMember($room, $request->token);
        if (! $me || $me->id !== $room->drawer_member_id) {
            return response()->json(['message' => 'forbidden'], 403);
        }
        $this->emit(new CanvasCleared($room->code, $request->token));

        return response()->json(['ok' => true]);
    }

    public function guess(Request $request, string $code)
    {
        $data = $request->validate(['token' => 'required|string|max:64', 'text' => 'required|string|max:60']);

        return DB::transaction(function () use ($data, $code) {
            $room = $this->findRoom($code, lock: true);
            if (! $room || $room->status !== 'playing' || $room->game !== 'pictionary' || blank($room->word)) {
                return response()->json(['ok' => false]);
            }
            $state = $room->state ?? [];
            if (($state['phase'] ?? null) !== 'play') {
                return response()->json(['ok' => false]);
            }
            $me = $this->getMember($room, $data['token']);
            if (! $me || $me->id === $room->drawer_member_id) {
                return response()->json(['message' => 'forbidden'], 403);
            }

            $correct = $state['correct'] ?? [];
            if (in_array($me->id, $correct)) {
                return response()->json(['ok' => true, 'already' => true]);
            }

            if ($this->normalize($data['text']) === $this->normalize($room->word)) {
                $order = count($correct) + 1;
                $points = max(1, 4 - $order);
                $this->award($me, $points);
                if ($room->drawer) {
                    $this->award($room->drawer, 1);
                }
                $correct[] = $me->id;
                $state['correct'] = $correct;
                $room->update(['state' => $state]);
                $room->load('members');
                $this->emit(new ChatPosted($room->code, 'correct', "¡{$me->name} adivinó!", $me->name, $me->id));
                // El primero que adivina gana y la ronda termina de inmediato.
                $this->finishPlay($room);

                return response()->json(['ok' => true, 'correct' => true]);
            }

            $this->emit(new ChatPosted($room->code, 'guess', $data['text'], $me->name, $me->id));

            return response()->json(['ok' => true, 'correct' => false]);
        });
    }

    // ---------------------------------------------------------------- Trivia
    public function answer(Request $request, string $code)
    {
        $data = $request->validate(['token' => 'required|string|max:64', 'index' => 'required|integer|min:0|max:3']);

        return DB::transaction(function () use ($data, $code) {
            $room = $this->findRoom($code, lock: true);
            if (! $room || $room->status !== 'playing' || $room->game !== 'trivia') {
                return response()->json(['ok' => false]);
            }
            $state = $room->state ?? [];
            if (($state['phase'] ?? null) !== 'play') {
                return response()->json(['ok' => false]);
            }
            $me = $this->getMember($room, $data['token']);
            if (! $me) {
                return response()->json(['message' => 'forbidden'], 403);
            }
            $answers = $state['answers'] ?? [];
            if (isset($answers[$me->id])) {
                return response()->json(['ok' => true, 'already' => true]);
            }

            $isCorrect = (int) $data['index'] === (int) $room->word;
            $pts = 0;
            if ($isCorrect) {
                $secs = max(1, (int) config('familia.trivia.round_seconds'));
                $remain = $room->round_ends_at ? max(0, $room->round_ends_at->getTimestamp() - now()->getTimestamp()) : 0;
                $pts = 2 + (int) round(($remain / $secs) * 3); // 2..5 según rapidez
                $this->award($me, $pts);
            }
            $answers[$me->id] = ['index' => (int) $data['index'], 'correct' => $isCorrect, 'pts' => $pts];
            $state['answers'] = $answers;
            $room->update(['state' => $state]);
            $room->load('members');
            $this->emit(new RoomUpdated($room));

            if (count($answers) >= $room->members()->count()) {
                $this->finishPlay($room);
            }

            return response()->json(['ok' => true, 'correct' => $isCorrect, 'pts' => $pts]);
        });
    }

    // ---------------------------------------------------------------- Tutti Frutti
    public function submit(Request $request, string $code)
    {
        $data = $request->validate([
            'token' => 'required|string|max:64',
            'answers' => 'array',
            'answers.*' => 'nullable|string|max:40',
        ]);

        return DB::transaction(function () use ($data, $code) {
            $room = $this->findRoom($code, lock: true);
            if (! $room || $room->status !== 'playing' || $room->game !== 'tuttifrutti') {
                return response()->json(['ok' => false]);
            }
            $state = $room->state ?? [];
            if (($state['phase'] ?? null) !== 'play') {
                return response()->json(['ok' => false]);
            }
            $me = $this->getMember($room, $data['token']);
            if (! $me) {
                return response()->json(['message' => 'forbidden'], 403);
            }
            $subs = $state['submissions'] ?? [];
            $subs[$me->id] = collect($data['answers'] ?? [])
                ->map(fn ($v) => is_string($v) ? mb_substr(trim($v), 0, 40) : '')->all();
            $state['submissions'] = $subs;
            $room->update(['state' => $state]);

            return response()->json(['ok' => true]);
        });
    }

    public function stop(Request $request, string $code)
    {
        $request->validate(['token' => 'required|string|max:64']);

        return DB::transaction(function () use ($request, $code) {
            $room = $this->findRoom($code, lock: true);
            if (! $room || $room->status !== 'playing' || $room->game !== 'tuttifrutti') {
                return response()->json(['ok' => false]);
            }
            if (($room->state['phase'] ?? null) !== 'play') {
                return response()->json(['ok' => false]);
            }
            $me = $this->getMember($room, $request->token);
            $this->emit(new ChatPosted($room->code, 'system', '¡' . ($me->name ?? 'Alguien') . ' dijo BASTA!'));
            $this->finishPlay($room);

            return response()->json(['ok' => true]);
        });
    }

    // ---------------------------------------------------------------- Ahorcado
    public function letter(Request $request, string $code)
    {
        $data = $request->validate(['token' => 'required|string|max:64', 'letter' => 'required|string|max:2']);

        return DB::transaction(function () use ($data, $code) {
            $room = $this->findRoom($code, lock: true);
            if (! $room || $room->status !== 'playing' || $room->game !== 'hangman') {
                return response()->json(['ok' => false]);
            }
            $state = $room->state ?? [];
            if (($state['phase'] ?? null) !== 'play') {
                return response()->json(['ok' => false]);
            }
            $me = $this->getMember($room, $data['token']);
            if (! $me) {
                return response()->json(['message' => 'forbidden'], 403);
            }
            $members = $room->members()->get();
            if (($state['turn'] ?? null) !== $me->id) {
                return response()->json(['ok' => false, 'message' => 'No es tu turno.'], 403);
            }
            $ch = mb_strtolower(trim($data['letter']));
            if (mb_strlen($ch) !== 1) {
                return response()->json(['ok' => false]);
            }
            $guessed = $state['guessed'] ?? [];
            if (in_array($ch, $guessed, true)) {
                return response()->json(['ok' => true, 'already' => true]);
            }
            $guessed[] = $ch;
            $state['guessed'] = $guessed;

            $wordLc = mb_strtolower($room->word);
            $hits = 0;
            foreach (mb_str_split($wordLc) as $c) {
                if ($c === $ch) {
                    $hits++;
                }
            }

            if ($hits > 0) {
                $this->award($me, $hits);   // +1 por letra revelada
                $complete = true;
                foreach (mb_str_split($wordLc) as $c) {
                    if ($c !== ' ' && ! in_array($c, $guessed, true)) {
                        $complete = false;
                        break;
                    }
                }
                if ($complete) {
                    $this->award($me, 2);   // bonus por completar
                    $state['solved'] = true;
                }
                $room->update(['state' => $state]);
                $room->load('members');
                $this->emit(new ChatPosted($room->code, 'correct', "{$me->name}: «" . mb_strtoupper($ch) . "» ✓ (+{$hits})", $me->name, $me->id));
                $this->emit(new RoomUpdated($room));
                if ($complete) {
                    $this->finishPlay($room);
                }
            } else {
                $state['misses'] = (int) ($state['misses'] ?? 0) + 1;
                $state['turn'] = $this->nextHangmanTurn($members, $me->id);   // falló → pasa el turno
                $room->update(['state' => $state]);
                $room->load('members');
                $this->emit(new ChatPosted($room->code, 'guess', mb_strtoupper($ch) . ' ✗', $me->name, $me->id));
                $this->emit(new RoomUpdated($room));
                if ($state['misses'] >= (int) config('familia.hangman.max_misses')) {
                    $this->finishPlay($room);
                }
            }

            return response()->json(['ok' => true, 'hit' => $hits > 0]);
        });
    }

    public function solve(Request $request, string $code)
    {
        $data = $request->validate(['token' => 'required|string|max:64', 'text' => 'required|string|max:60']);

        return DB::transaction(function () use ($data, $code) {
            $room = $this->findRoom($code, lock: true);
            if (! $room || $room->status !== 'playing' || $room->game !== 'hangman') {
                return response()->json(['ok' => false]);
            }
            $state = $room->state ?? [];
            if (($state['phase'] ?? null) !== 'play') {
                return response()->json(['ok' => false]);
            }
            $me = $this->getMember($room, $data['token']);
            if (! $me) {
                return response()->json(['message' => 'forbidden'], 403);
            }
            $members = $room->members()->get();
            if (($state['turn'] ?? null) !== $me->id) {
                return response()->json(['ok' => false, 'message' => 'No es tu turno.'], 403);
            }

            if ($this->normalize($data['text']) === $this->normalize($room->word)) {
                $letters = [];
                foreach (mb_str_split(mb_strtolower($room->word)) as $c) {
                    if ($c !== ' ') {
                        $letters[$c] = true;
                    }
                }
                $state['guessed'] = array_keys($letters);
                $state['solved'] = true;
                $this->award($me, 5);   // resolver la palabra completa
                $room->update(['state' => $state]);
                $room->load('members');
                $this->emit(new ChatPosted($room->code, 'correct', "¡{$me->name} resolvió la palabra! (+5)", $me->name, $me->id));
                $this->finishPlay($room);

                return response()->json(['ok' => true, 'correct' => true]);
            }

            $state['misses'] = (int) ($state['misses'] ?? 0) + 1;
            $state['turn'] = $this->nextHangmanTurn($members, $me->id);   // falló → pasa el turno
            $room->update(['state' => $state]);
            $room->load('members');
            $this->emit(new ChatPosted($room->code, 'guess', $data['text'] . ' ✗', $me->name, $me->id));
            $this->emit(new RoomUpdated($room));
            if ($state['misses'] >= (int) config('familia.hangman.max_misses')) {
                $this->finishPlay($room);
            }

            return response()->json(['ok' => true, 'correct' => false]);
        });
    }

    // ---------------------------------------------------------------- Memoria
    public function flip(Request $request, string $code)
    {
        $data = $request->validate(['token' => 'required|string|max:64', 'card' => 'required|integer|min:0']);

        return DB::transaction(function () use ($data, $code) {
            $room = $this->findRoom($code, lock: true);
            if (! $room || $room->status !== 'playing' || $room->game !== 'memoria') {
                return response()->json(['ok' => false]);
            }
            $state = $room->state ?? [];
            if (($state['phase'] ?? null) !== 'play' || ! empty($state['resolve_at'])) {
                return response()->json(['ok' => false]);   // esperando que se tapen las anteriores
            }
            $me = $this->getMember($room, $data['token']);
            if (! $me) {
                return response()->json(['message' => 'forbidden'], 403);
            }
            if (($state['turn'] ?? null) !== $me->id) {
                return response()->json(['ok' => false, 'message' => 'No es tu turno.'], 403);
            }

            $cards = $state['cards'] ?? [];
            $found = $state['found'] ?? [];
            $flipped = $state['flipped'] ?? [];
            $cardId = (int) $data['card'];
            $card = collect($cards)->firstWhere('id', $cardId);
            if (! $card || in_array($cardId, $found, true) || in_array($cardId, $flipped, true) || count($flipped) >= 2) {
                return response()->json(['ok' => false]);
            }

            $flipped[] = $cardId;
            if (count($flipped) === 1) {
                $state['flipped'] = $flipped;
                $room->update(['state' => $state]);
                $this->emit(new RoomUpdated($room->fresh('members')));
                return response()->json(['ok' => true]);
            }

            // Segunda carta → comparar
            $first = collect($cards)->firstWhere('id', $flipped[0]);
            if (($first['value'] ?? null) === $card['value']) {
                $state['found'] = array_merge($found, $flipped);
                $state['flipped'] = [];
                $this->award($me, 1);   // par encontrado → sigue el mismo jugador
                $room->update(['state' => $state]);
                $room->load('members');
                $this->emit(new ChatPosted($room->code, 'correct', "{$me->name} encontró un par 🎉 (+1)", $me->name, $me->id));
                $this->emit(new RoomUpdated($room));
                if (count($state['found']) >= count($cards)) {
                    $this->finishPlay($room);   // se completó el tablero
                }
            } else {
                $state['flipped'] = $flipped;   // ambas visibles un instante
                $state['resolve_at'] = now()->addMilliseconds((int) config('familia.memoria.flip_ms'))->toIso8601String();
                $room->update(['state' => $state]);
                $this->emit(new RoomUpdated($room->fresh('members')));
            }

            return response()->json(['ok' => true]);
        });
    }

    public function resolveFlip(Request $request, string $code)
    {
        $request->validate(['token' => 'required|string|max:64']);

        return DB::transaction(function () use ($code) {
            $room = $this->findRoom($code, lock: true);
            if (! $room || $room->status !== 'playing' || $room->game !== 'memoria') {
                return response()->json(['ok' => false]);
            }
            $state = $room->state ?? [];
            if (($state['phase'] ?? null) !== 'play' || empty($state['resolve_at'])) {
                return response()->json(['ok' => false]);
            }
            if (\Illuminate\Support\Carbon::parse($state['resolve_at'])->gt(now()->addSecond())) {
                return response()->json(['ok' => false]);   // todavía visibles
            }
            $members = $room->members()->get();
            $state['flipped'] = [];
            $state['turn'] = $this->nextHangmanTurn($members, $state['turn'] ?? null);   // no coincidió → pasa el turno
            unset($state['resolve_at']);
            $room->update(['state' => $state]);
            $this->emit(new RoomUpdated($room->fresh('members')));

            return response()->json(['ok' => true]);
        });
    }

    public function leave(Request $request, string $code)
    {
        $request->validate(['token' => 'required|string|max:64']);
        $room = $this->findRoom($code);
        if ($room) {
            $me = $this->getMember($room, $request->token);
            if ($me) {
                if ($room->status === 'lobby' && ! $me->is_host) {
                    $me->delete();
                } else {
                    $me->update(['last_seen_at' => now()->subMinutes(5)]);
                }
                $this->emit(new RoomUpdated($room->fresh('members')));
            }
        }

        return response()->json(['ok' => true]);
    }

    // ================================================================ ciclo de ronda
    private function startNextRound(FamilyRoom $room): void
    {
        $members = $room->members()->orderBy('slot')->get();
        $next = $room->round + 1;
        if ($next > $room->total_rounds || $members->count() < config('familia.min_families')) {
            $this->endGame($room);
            return;
        }

        $state = $room->state ?? [];
        $state['phase'] = 'play';
        unset($state['reveal']);
        $used = $state['used'] ?? [];
        $update = ['round' => $next, 'round_started_at' => now(), 'drawer_member_id' => null, 'word' => null];

        if ($room->game === 'pictionary') {
            $drawer = $members[($next - 1) % $members->count()];
            $word = $this->pickOne(config('familia.pictionary.words'), $used);
            $used[] = $word;
            $state['correct'] = [];
            $update['drawer_member_id'] = $drawer->id;
            $update['word'] = $word;
            $update['round_ends_at'] = now()->addSeconds((int) config('familia.pictionary.round_seconds'));
            $sys = "Ronda {$next}/{$room->total_rounds} — dibuja {$drawer->name}.";
        } elseif ($room->game === 'trivia') {
            $questions = config('familia.trivia.questions');
            $q = $this->pickTrivia($questions, $used);
            $used[] = $q['q'];
            $state['question'] = $q['q'];
            $state['options'] = $q['options'];
            $state['answers'] = [];
            $update['word'] = (string) $q['answer'];
            $update['round_ends_at'] = now()->addSeconds((int) config('familia.trivia.round_seconds'));
            $sys = "Pregunta {$next}/{$room->total_rounds}";
        } elseif ($room->game === 'tuttifrutti') {
            $letter = $this->pickOne(config('familia.tuttifrutti.letters'), $used);
            $used[] = $letter;
            $state['letter'] = $letter;
            $state['categories'] = config('familia.tuttifrutti.categories');
            $state['submissions'] = [];
            $update['round_ends_at'] = now()->addSeconds((int) config('familia.tuttifrutti.round_seconds'));
            $sys = "Ronda {$next}/{$room->total_rounds} — letra «{$letter}»";
        } elseif ($room->game === 'hangman') {
            $word = $this->pickOne(config('familia.hangman.words'), $used);
            $used[] = $word;
            $state['guessed'] = [];
            $state['misses'] = 0;
            $state['turn'] = $members->first()->id;   // arranca el de menor slot
            $update['word'] = $word;   // secreta (no se expone en el snapshot)
            $update['round_ends_at'] = now()->addSeconds((int) config('familia.hangman.round_seconds'));
            $sys = "Ronda {$next}/{$room->total_rounds} — Ahorcado: empieza {$members->first()->name}.";
        } else { // memoria
            $faces = config('familia.memoria.faces');
            shuffle($faces);
            $chosen = array_slice($faces, 0, (int) config('familia.memoria.pairs'));
            $deck = [];
            $id = 0;
            foreach ($chosen as $face) {
                $deck[] = ['id' => $id++, 'value' => $face];
                $deck[] = ['id' => $id++, 'value' => $face];
            }
            shuffle($deck);
            $state['cards'] = $deck;      // secreto: el snapshot solo revela lo destapado
            $state['flipped'] = [];
            $state['found'] = [];
            $state['turn'] = $members->first()->id;
            unset($state['resolve_at']);
            $update['round_ends_at'] = now()->addSeconds((int) config('familia.memoria.round_seconds'));
            $sys = "Ronda {$next}/{$room->total_rounds} — Memoria: empieza {$members->first()->name}.";
        }

        $state['used'] = $used;
        $update['state'] = $state;
        $room->update($update);
        $room->load('members');
        $this->emit(new RoomUpdated($room));
        $this->emit(new ChatPosted($room->code, 'system', $sys));
    }

    private function finishPlay(FamilyRoom $room): void
    {
        $state = $room->state ?? [];
        if (($state['phase'] ?? null) !== 'play') {
            return;
        }
        $members = $room->members()->get();

        // Tutti Frutti pasa a una fase de validación (votación anti-trampa) antes de puntuar.
        if ($room->game === 'tuttifrutti') {
            $this->startValidation($room, $state, $members);
            return;
        }

        if ($room->game === 'pictionary') {
            $winnerId = ($state['correct'] ?? [])[0] ?? null;
            $winner = $winnerId ? optional($members->firstWhere('id', $winnerId))->name : null;
            $reveal = ['word' => $room->word, 'winner' => $winner];
        } elseif ($room->game === 'trivia') {
            $correct = (int) $room->word;
            $answers = $state['answers'] ?? [];
            $reveal = [
                'question' => $state['question'] ?? '',
                'options' => $state['options'] ?? [],
                'answer' => $correct,
                'breakdown' => $members->map(fn ($m) => [
                    'member_id' => $m->id,
                    'name' => $m->name,
                    'chosen' => $answers[$m->id]['index'] ?? null,
                    'correct' => $answers[$m->id]['correct'] ?? false,
                    'pts' => $answers[$m->id]['pts'] ?? 0,
                ])->values(),
            ];
        } elseif ($room->game === 'hangman') {
            $reveal = ['word' => $room->word, 'solved' => (bool) ($state['solved'] ?? false)];
        } else { // memoria
            $reveal = ['done' => true];
        }

        $state['phase'] = 'reveal';
        $state['reveal'] = $reveal;
        $revealSecs = (int) (config("familia.{$room->game}.reveal_seconds") ?? config('familia.reveal_seconds'));
        $room->update([
            'state' => $state,
            'round_ends_at' => now()->addSeconds($revealSecs),
        ]);
        $room->load('members');
        $this->emit(new RoomUpdated($room));
        if ($room->game === 'pictionary') {
            $this->emit(new ChatPosted($room->code, 'system', "La palabra era: {$reveal['word']}"));
        }
    }

    private function advance(FamilyRoom $room): void
    {
        if ($room->round >= $room->total_rounds) {
            $this->endGame($room);
        } else {
            $this->startNextRound($room);
        }
    }

    private function endGame(FamilyRoom $room): void
    {
        $room->load('members');
        $playlist = $room->playlist ?: [];
        $pos = (int) $room->playlist_pos;
        $multi = count($playlist) > 1;
        $hasNext = count($playlist) > $pos + 1;

        $gamePodium = $this->podium($room->members, 'game_score');
        $gameWinner = $this->winnerName($room->members, 'game_score');

        // ¿Hay otro juego en la tanda? → intermedio con el ganador de la partida, luego sigue
        if ($hasNext) {
            $nextGame = $playlist[$pos + 1];
            $room->update([
                'status' => 'playing', 'word' => null, 'drawer_member_id' => null,
                'round_ends_at' => now()->addSeconds(7),
                'state' => ['phase' => 'gameover', 'result' => [
                    'label' => $this->gameLabel($room->game),
                    'winner' => $gameWinner,
                    'podium' => $gamePodium,
                    'next' => $this->gameLabel($nextGame),
                    'final' => false,
                ]],
            ]);
            $room->load('members');
            $this->emit(new RoomUpdated($room));
            $this->emit(new ChatPosted($room->code, 'system', '🏆 ' . $this->gameLabel($room->game) . ': ' . $gameWinner));
            return;
        }

        // Última partida → fin de la tanda (total)
        $totalWinner = $this->winnerName($room->members, 'score');
        $room->update([
            'status' => 'ended', 'word' => null, 'drawer_member_id' => null, 'round_ends_at' => null,
            'state' => ['phase' => 'ended', 'result' => [
                'label' => $multi ? 'Total de la tanda' : $this->gameLabel($room->game),
                'winner' => $multi ? $totalWinner : $gameWinner,
                'podium' => $this->podium($room->members, $multi ? 'score' : 'game_score'),
                'final' => true,
            ]],
        ]);
        $room->load('members');
        $this->emit(new RoomUpdated($room));
        if ($multi) {
            $this->emit(new ChatPosted($room->code, 'system', '🏆 ' . $this->gameLabel($room->game) . ': ' . $gameWinner));
        }
        $this->emit(new ChatPosted($room->code, 'system', '🏆 ' . ($multi ? 'TOTAL' : 'Ganador') . ': ' . ($multi ? $totalWinner : $gameWinner)));
    }

    private function advanceToNextGame(FamilyRoom $room): void
    {
        $playlist = $room->playlist ?: [];
        $pos = (int) $room->playlist_pos;
        if (count($playlist) <= $pos + 1) {
            return;
        }
        $room->update(['playlist_pos' => $pos + 1, 'game' => $playlist[$pos + 1]]);
        $this->beginGame($room, resetScores: false);
    }

    private function podium($members, string $field): array
    {
        return $members->sortByDesc($field)->values()
            ->map(fn ($m) => ['name' => $m->name, 'pts' => (int) $m->{$field}])->all();
    }

    private function winnerName($members, string $field): string
    {
        if ($members->isEmpty()) {
            return '—';
        }
        $max = (int) $members->max($field);
        $top = $members->where($field, $max);
        return $top->count() > 1
            ? 'Empate: ' . $top->pluck('name')->join(', ') . " ({$max})"
            : optional($top->first())->name . " ({$max})";
    }

    private function gameLabel(string $g): string
    {
        return ['pictionary' => 'Dibuja y Adivina', 'trivia' => 'Trivia', 'tuttifrutti' => 'Tutti Frutti', 'hangman' => 'Ahorcado', 'memoria' => 'Memoria'][$g] ?? $g;
    }

    /** Siguiente turno del Ahorcado (por slot, salteando a los desconectados). */
    private function nextHangmanTurn($members, ?int $currentId): ?int
    {
        $ordered = $members->sortBy('slot')->values();
        if ($ordered->isEmpty()) {
            return null;
        }
        $idx = $ordered->search(fn ($m) => $m->id === $currentId);
        $idx = $idx === false ? -1 : $idx;
        $n = $ordered->count();
        for ($i = 1; $i <= $n; $i++) {
            $cand = $ordered[($idx + $i) % $n];
            if ($cand->last_seen_at && $cand->last_seen_at->gt(now()->subSeconds(40))) {
                return $cand->id;   // preferimos a alguien conectado
            }
        }
        return $ordered[($idx + 1) % $n]->id;   // si nadie está online, el siguiente por slot
    }

    /** Arma la grilla de respuestas y abre la fase de validación (votación). */
    private function startValidation(FamilyRoom $room, array $state, $members): void
    {
        $letter = $this->normalize($state['letter'] ?? '');
        $cats = $state['categories'] ?? [];
        $subs = $state['submissions'] ?? [];

        $entries = [];
        foreach ($members as $m) {
            $ans = $subs[$m->id] ?? [];
            $answers = [];
            foreach ($cats as $ci => $cat) {
                $val = trim((string) ($ans[$ci] ?? ''));
                $norm = $this->normalize($val);
                // Descartamos automáticamente las que no empiezan con la letra pedida.
                $letterOk = $norm !== '' && $letter !== '' && str_starts_with($norm, $letter);
                $answers[] = ['cat' => $cat, 'value' => $val, 'letter_ok' => $letterOk];
            }
            $entries[] = ['owner_id' => $m->id, 'name' => $m->name, 'answers' => $answers];
        }

        $state['phase'] = 'validate';
        $state['entries'] = $entries;
        $state['votes'] = [];   // { voterId: ["ownerId:catIndex", ...] } → rechazos
        $room->update([
            'state' => $state,
            'round_ends_at' => now()->addSeconds((int) config('familia.tuttifrutti.validate_seconds')),
        ]);
        $room->load('members');
        $this->emit(new RoomUpdated($room));
        $this->emit(new ChatPosted($room->code, 'system', 'Revisen las respuestas y destilden las que no valgan.'));
    }

    /** Cierra la validación: puntúa solo las respuestas válidas y aceptadas. */
    private function finishValidate(FamilyRoom $room): void
    {
        $state = $room->state ?? [];
        if (($state['phase'] ?? null) !== 'validate') {
            return;
        }
        $members = $room->members()->get();
        $reviewers = max(1, $members->count() - 1);
        $cats = $state['categories'] ?? [];
        $entries = $state['entries'] ?? [];
        $votes = $state['votes'] ?? [];

        // Conteo de rechazos por "owner:cat"
        $rej = [];
        foreach ($votes as $keys) {
            foreach ((array) $keys as $k) {
                $rej[$k] = ($rej[$k] ?? 0) + 1;
            }
        }
        // Rechazada si la mayoría de los revisores la destildó.
        $isRejected = fn ($owner, $ci) => (($rej["{$owner}:{$ci}"] ?? 0) * 2) > $reviewers;

        // Normalizadas que cuentan (válidas por letra Y aceptadas)
        $normByCat = [];
        foreach ($entries as $e) {
            foreach ($cats as $ci => $cat) {
                $a = $e['answers'][$ci] ?? null;
                $ok = $a && ! empty($a['letter_ok']) && ! $isRejected($e['owner_id'], $ci);
                $normByCat[$ci][$e['owner_id']] = $ok ? $this->normalize($a['value']) : null;
            }
        }

        $rows = [];
        foreach ($entries as $e) {
            $m = $members->firstWhere('id', $e['owner_id']);
            $rowAnswers = [];
            $total = 0;
            foreach ($cats as $ci => $cat) {
                $a = $e['answers'][$ci] ?? ['value' => '', 'letter_ok' => false];
                $norm = $normByCat[$ci][$e['owner_id']] ?? null;
                $p = 0;
                if ($norm !== null) {
                    $count = 0;
                    foreach ($normByCat[$ci] as $n) {
                        if ($n !== null && $n === $norm) {
                            $count++;
                        }
                    }
                    $p = $count === 1 ? 10 : 5;
                }
                $total += $p;
                $rowAnswers[] = [
                    'cat' => $cat,
                    'value' => $a['value'],
                    'pts' => $p,
                    'letter_ok' => (bool) ($a['letter_ok'] ?? false),
                    'rejected' => $isRejected($e['owner_id'], $ci),
                ];
            }
            if ($m) {
                $this->award($m, $total);
            }
            $rows[] = ['member_id' => $e['owner_id'], 'name' => $e['name'], 'answers' => $rowAnswers, 'total' => $total];
        }

        $state['phase'] = 'reveal';
        $state['reveal'] = ['letter' => $state['letter'] ?? '', 'categories' => $cats, 'rows' => $rows];
        unset($state['entries'], $state['votes']);
        $revealSecs = (int) (config('familia.tuttifrutti.reveal_seconds') ?? config('familia.reveal_seconds'));
        $room->update(['state' => $state, 'round_ends_at' => now()->addSeconds($revealSecs)]);
        $room->load('members');
        $this->emit(new RoomUpdated($room));
    }

    /** Voto de validación en Tutti Frutti (aceptar/rechazar la respuesta de otra familia). */
    public function vote(Request $request, string $code)
    {
        $data = $request->validate([
            'token' => 'required|string|max:64',
            'owner' => 'required|integer',
            'cat' => 'required|integer|min:0',
            'accept' => 'required|boolean',
        ]);

        return DB::transaction(function () use ($data, $code) {
            $room = $this->findRoom($code, lock: true);
            if (! $room || $room->game !== 'tuttifrutti' || ($room->state['phase'] ?? null) !== 'validate') {
                return response()->json(['ok' => false]);
            }
            $me = $this->getMember($room, $data['token']);
            if (! $me || $me->id === (int) $data['owner']) {
                return response()->json(['message' => 'forbidden'], 403); // no podés votar tu propia respuesta
            }
            $state = $room->state;
            $votes = $state['votes'] ?? [];
            $mine = $votes[$me->id] ?? [];
            $key = $data['owner'] . ':' . $data['cat'];
            $mine = array_values(array_filter($mine, fn ($k) => $k !== $key));
            if (! $data['accept']) {
                $mine[] = $key; // destildar = rechazar
            }
            $votes[$me->id] = $mine;
            $state['votes'] = $votes;
            $room->update(['state' => $state]);
            $this->emit(new RoomUpdated($room->fresh('members')));

            return response()->json(['ok' => true]);
        });
    }

    // ---------------------------------------------------------------- utilidades
    private function emit(object $event): void
    {
        // broadcast() difiere el envío al __destruct del PendingBroadcast, así que
        // hay que forzar su destrucción DENTRO del try (con unset) para que una
        // caída de Reverb no rompa la petición.
        rescue(function () use ($event) {
            $pending = broadcast($event);
            unset($pending);
        }, report: false);
    }

    /** Suma puntos al total de la tanda (score) y al de la partida actual (game_score). */
    private function award(FamilyMember $m, int $pts): void
    {
        $m->increment('score', $pts);
        $m->increment('game_score', $pts);
    }

    private function findRoom(string $code, bool $lock = false): ?FamilyRoom
    {
        $q = FamilyRoom::where('code', strtoupper($code));
        if ($lock) {
            $q->lockForUpdate();
        }
        return $q->first();
    }

    private function getMember(FamilyRoom $room, ?string $token): ?FamilyMember
    {
        if (blank($token)) {
            return null;
        }
        return $room->members()->where('token', $token)->first();
    }

    private function mePayload(FamilyRoom $room, ?FamilyMember $me): ?array
    {
        if (! $me) {
            return null;
        }
        return [
            'id' => $me->id,
            'name' => $me->name,
            'slot' => $me->slot,
            'score' => $me->score,
            'is_host' => (bool) $me->is_host,
            'is_drawer' => $me->id === $room->drawer_member_id,
        ];
    }

    private function pickOne(array $pool, array $used): string
    {
        $available = array_values(array_diff($pool, $used));
        if (empty($available)) {
            $available = $pool;
        }
        return $available[array_rand($available)];
    }

    private function pickTrivia(array $questions, array $used): array
    {
        $available = array_values(array_filter($questions, fn ($q) => ! in_array($q['q'], $used)));
        if (empty($available)) {
            $available = $questions;
        }
        return $available[array_rand($available)];
    }

    private function normalize(string $s): string
    {
        $s = Str::lower(Str::ascii(trim($s)));
        $s = preg_replace('/[^a-z0-9 ]/', '', $s);
        return preg_replace('/\s+/', ' ', $s);
    }

    private function uniqueCode(): string
    {
        do {
            $code = '';
            for ($i = 0; $i < 5; $i++) {
                $code .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
            }
        } while (FamilyRoom::where('code', $code)->exists());

        return $code;
    }
}
