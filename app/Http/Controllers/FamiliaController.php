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
    private const GAMES = ['pictionary', 'trivia', 'tuttifrutti'];

    // ---------------------------------------------------------------- páginas
    public function index()
    {
        return Inertia::render('Familia/Index', [
            'seo' => [
                'title' => 'FIFARDOS Familia — Minijuegos en vivo para jugar en familia',
                'description' => 'Creá una sala, compartí el código y jugá Dibuja y Adivina, Trivia o Tutti Frutti '
                    . 'con hasta 3 familias desde cualquier parte del mundo, en tiempo real y gratis.',
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
                return response()->json(['message' => 'La sala está llena (máximo 3 familias).'], 422);
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
            $this->emit(new ChatPosted($room->code, 'system', "La familia {$data['name']} se unió a la sala."));

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
            if ($room->status !== 'lobby') {
                return response()->json(['message' => 'la partida ya empezó'], 422);
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
        $request->validate(['token' => 'required|string|max:64']);

        return DB::transaction(function () use ($request, $code) {
            $room = $this->findRoom($code, lock: true);
            if (! $room) {
                return response()->json(['message' => 'not found'], 404);
            }
            $me = $this->getMember($room, $request->token);
            if (! $me || ! $me->is_host) {
                return response()->json(['message' => 'solo el anfitrión puede empezar'], 403);
            }
            $count = $room->members()->count();
            if ($count < config('familia.min_families')) {
                return response()->json(['message' => 'Se necesitan al menos 2 familias.'], 422);
            }

            $total = match ($room->game) {
                'trivia' => min((int) config('familia.trivia.rounds'), count(config('familia.trivia.questions'))),
                'tuttifrutti' => (int) config('familia.tuttifrutti.rounds'),
                default => $count * (int) config('familia.pictionary.rounds_per_family'),
            };

            $room->members()->update(['score' => 0]);
            $room->update([
                'status' => 'playing',
                'round' => 0,
                'total_rounds' => $total,
                'state' => ['used' => []],
            ]);
            $this->startNextRound($room);

            return response()->json(['ok' => true]);
        });
    }

    public function timeout(Request $request, string $code)
    {
        return DB::transaction(function () use ($code) {
            $room = $this->findRoom($code, lock: true);
            if (! $room || $room->status !== 'playing') {
                return response()->json(['ok' => false]);
            }
            if ($room->round_ends_at && $room->round_ends_at->isFuture()) {
                return response()->json(['ok' => false]);
            }
            $phase = $room->state['phase'] ?? null;
            if ($phase === 'play') {
                $this->finishPlay($room);
            } elseif ($phase === 'reveal') {
                $this->advance($room);
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
                $me->increment('score', $points);
                if ($room->drawer) {
                    $room->drawer->increment('score', 1);
                }
                $correct[] = $me->id;
                $state['correct'] = $correct;
                $room->update(['state' => $state]);
                $room->load('members');
                $this->emit(new ChatPosted($room->code, 'correct', "¡{$me->name} adivinó!", $me->name, $me->id));
                $this->emit(new RoomUpdated($room));

                $guessers = $room->members()->where('id', '!=', $room->drawer_member_id)->count();
                if (count($correct) >= $guessers) {
                    $this->finishPlay($room);
                }

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
                $me->increment('score', $pts);
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
            $sys = "Ronda {$next}/{$room->total_rounds} — dibuja la familia {$drawer->name}.";
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
        } else { // tuttifrutti
            $letter = $this->pickOne(config('familia.tuttifrutti.letters'), $used);
            $used[] = $letter;
            $state['letter'] = $letter;
            $state['categories'] = config('familia.tuttifrutti.categories');
            $state['submissions'] = [];
            $update['round_ends_at'] = now()->addSeconds((int) config('familia.tuttifrutti.round_seconds'));
            $sys = "Ronda {$next}/{$room->total_rounds} — letra «{$letter}»";
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

        if ($room->game === 'pictionary') {
            $reveal = ['word' => $room->word];
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
        } else { // tuttifrutti
            $reveal = $this->scoreTuttiFrutti($room, $state, $members);
        }

        $state['phase'] = 'reveal';
        $state['reveal'] = $reveal;
        $room->update([
            'state' => $state,
            'round_ends_at' => now()->addSeconds((int) config('familia.reveal_seconds')),
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
        $room->update([
            'status' => 'ended',
            'word' => null,
            'drawer_member_id' => null,
            'round_ends_at' => null,
            'state' => ['phase' => 'ended'],
        ]);
        $room->load('members');

        $top = $room->members->sortByDesc('score')->first();
        $winners = $room->members->where('score', optional($top)->score);
        $msg = $winners->count() > 1
            ? '¡Empate! ' . $winners->pluck('name')->join(', ')
            : '🏆 ¡Ganó la familia ' . optional($top)->name . '!';

        $this->emit(new RoomUpdated($room));
        $this->emit(new ChatPosted($room->code, 'system', $msg));
    }

    private function scoreTuttiFrutti(FamilyRoom $room, array $state, $members): array
    {
        $letter = $this->normalize($state['letter'] ?? '');
        $cats = $state['categories'] ?? [];
        $subs = $state['submissions'] ?? [];

        // Respuesta normalizada por categoría y familia (null si inválida)
        $normByCat = [];
        foreach ($members as $m) {
            $ans = $subs[$m->id] ?? [];
            foreach ($cats as $ci => $cat) {
                $norm = $this->normalize((string) ($ans[$ci] ?? ''));
                $valid = $norm !== '' && $letter !== '' && str_starts_with($norm, $letter);
                $normByCat[$ci][$m->id] = $valid ? $norm : null;
            }
        }

        $rows = [];
        foreach ($members as $m) {
            $ans = $subs[$m->id] ?? [];
            $rowAnswers = [];
            $total = 0;
            foreach ($cats as $ci => $cat) {
                $norm = $normByCat[$ci][$m->id] ?? null;
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
                $rowAnswers[] = ['cat' => $cat, 'value' => (string) ($ans[$ci] ?? ''), 'pts' => $p];
            }
            $m->increment('score', $total);
            $rows[] = ['member_id' => $m->id, 'name' => $m->name, 'answers' => $rowAnswers, 'total' => $total];
        }

        return ['letter' => $state['letter'] ?? '', 'categories' => $cats, 'rows' => $rows];
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
