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

    // ---------------------------------------------------------------- páginas
    public function index()
    {
        return Inertia::render('Familia/Index', [
            'seo' => [
                'title' => 'FIFARDOS Familia — Minijuegos en vivo para jugar en familia',
                'description' => 'Creá una sala, compartí el código y jugá Dibuja y Adivina con hasta 3 familias '
                    . 'desde cualquier parte del mundo, en tiempo real y gratis.',
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
                'round_seconds' => (int) config('familia.round_seconds'),
            ],
        ]);
    }

    // ---------------------------------------------------------------- salas
    public function create(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:24',
            'token' => 'required|string|max:64',
        ]);

        $room = FamilyRoom::create([
            'code' => $this->uniqueCode(),
            'game' => 'pictionary',
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

            // Reconexión con el mismo token: actualizamos y devolvemos
            $existing = $room->members()->where('token', $data['token'])->first();
            if ($existing) {
                $existing->update(['name' => trim($data['name']), 'last_seen_at' => now()]);
                $this->emit(new RoomUpdated($room->fresh('members')));
                return response()->json(['code' => $room->code, 'slot' => $existing->slot]);
            }

            if ($room->status !== 'lobby') {
                return response()->json(['message' => 'La partida ya empezó.'], 422);
            }
            $count = $room->members()->count();
            if ($count >= config('familia.max_families')) {
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

    // ---------------------------------------------------------------- presencia / identidad
    public function hello(Request $request, string $code)
    {
        $request->validate(['token' => 'required|string|max:64']);
        $room = $this->findRoom($code);
        if (! $room) {
            return response()->json(['message' => 'not found'], 404);
        }
        $me = $room->members()->where('token', $request->token)->first();
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
        $me = $room->members()->where('token', $request->query('token'))->first();

        return response()->json([
            'room' => $room->publicSnapshot(),
            'me' => $this->mePayload($room, $me),
        ]);
    }

    public function word(Request $request, string $code)
    {
        $room = $this->findRoom($code);
        if (! $room || $room->status !== 'playing') {
            return response()->json(['message' => 'no word'], 404);
        }
        $me = $room->members()->where('token', $request->query('token'))->first();
        if (! $me || $me->id !== $room->drawer_member_id) {
            return response()->json(['message' => 'forbidden'], 403);
        }

        return response()->json(['word' => $room->word]);
    }

    // ---------------------------------------------------------------- juego
    public function start(Request $request, string $code)
    {
        $request->validate(['token' => 'required|string|max:64']);

        return DB::transaction(function () use ($request, $code) {
            $room = $this->findRoom($code, lock: true);
            if (! $room) {
                return response()->json(['message' => 'not found'], 404);
            }
            $me = $room->members()->where('token', $request->token)->first();
            if (! $me || ! $me->is_host) {
                return response()->json(['message' => 'solo el anfitrión puede empezar'], 403);
            }
            if ($room->members()->count() < config('familia.min_families')) {
                return response()->json(['message' => 'Se necesitan al menos 2 familias.'], 422);
            }

            // Reinicia puntajes y arranca
            $room->members()->update(['score' => 0]);
            $room->update([
                'status' => 'playing',
                'round' => 0,
                'total_rounds' => $room->members()->count() * (int) config('familia.rounds_per_family'),
                'state' => ['used_words' => []],
            ]);
            $this->startNextRound($room);

            return response()->json(['ok' => true]);
        });
    }

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
        if (! $room || $room->status !== 'playing') {
            return response()->json(['message' => 'no'], 409);
        }
        $me = $room->members()->where('token', $data['token'])->first();
        if (! $me || $me->id !== $room->drawer_member_id) {
            return response()->json(['message' => 'forbidden'], 403);
        }

        $this->emit(new DrawStroke(
            $room->code,
            $data['token'],
            $data['points'],
            $data['color'] ?? '#111111',
            (float) ($data['size'] ?? 4),
            (bool) ($data['begin'] ?? false),
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
        $me = $room->members()->where('token', $request->token)->first();
        if (! $me || $me->id !== $room->drawer_member_id) {
            return response()->json(['message' => 'forbidden'], 403);
        }
        $this->emit(new CanvasCleared($room->code, $request->token));

        return response()->json(['ok' => true]);
    }

    public function guess(Request $request, string $code)
    {
        $data = $request->validate([
            'token' => 'required|string|max:64',
            'text' => 'required|string|max:60',
        ]);

        return DB::transaction(function () use ($data, $code) {
            $room = $this->findRoom($code, lock: true);
            if (! $room || $room->status !== 'playing' || blank($room->word)) {
                return response()->json(['ok' => false]);
            }
            $me = $room->members()->where('token', $data['token'])->first();
            if (! $me || $me->id === $room->drawer_member_id) {
                return response()->json(['message' => 'forbidden'], 403);
            }

            $state = $room->state ?? [];
            $correct = $state['correct'] ?? [];
            if (in_array($me->id, $correct)) {
                return response()->json(['ok' => true, 'already' => true]);
            }

            if ($this->normalize($data['text']) === $this->normalize($room->word)) {
                $order = count($correct) + 1;          // 1er acierto = más puntos
                $points = max(1, 4 - $order);          // 3, 2, 1...
                $me->increment('score', $points);
                if ($room->drawer) {
                    $room->drawer->increment('score', 1); // el dibujante suma por cada acierto
                }
                $correct[] = $me->id;
                $state['correct'] = $correct;
                $room->update(['state' => $state]);

                $room->load('members');
                $this->emit(new ChatPosted($room->code, 'correct', "¡{$me->name} adivinó!", $me->name, $me->id));
                $this->emit(new RoomUpdated($room));

                // ¿Adivinaron todas las familias que no dibujan? → fin de ronda
                $guessers = $room->members()->where('id', '!=', $room->drawer_member_id)->count();
                if (count($correct) >= $guessers) {
                    $this->endRound($room);
                }

                return response()->json(['ok' => true, 'correct' => true]);
            }

            // Adivinanza incorrecta → va al chat de todos
            $this->emit(new ChatPosted($room->code, 'guess', $data['text'], $me->name, $me->id));

            return response()->json(['ok' => true, 'correct' => false]);
        });
    }

    public function roundTimeout(Request $request, string $code)
    {
        return DB::transaction(function () use ($code) {
            $room = $this->findRoom($code, lock: true);
            if (! $room || $room->status !== 'playing' || blank($room->word)) {
                return response()->json(['ok' => false]);
            }
            if ($room->round_ends_at && $room->round_ends_at->isFuture()) {
                return response()->json(['ok' => false]); // todavía no
            }
            $this->endRound($room);

            return response()->json(['ok' => true]);
        });
    }

    public function leave(Request $request, string $code)
    {
        $request->validate(['token' => 'required|string|max:64']);
        $room = $this->findRoom($code);
        if ($room) {
            $me = $room->members()->where('token', $request->token)->first();
            if ($me) {
                if ($room->status === 'lobby' && ! $me->is_host) {
                    $me->delete();
                } else {
                    $me->update(['last_seen_at' => now()->subMinutes(5)]); // marcar offline
                }
                $this->emit(new RoomUpdated($room->fresh('members')));
            }
        }

        return response()->json(['ok' => true]);
    }

    // ---------------------------------------------------------------- helpers de ronda
    private function startNextRound(FamilyRoom $room): void
    {
        $members = $room->members()->orderBy('slot')->get();
        $next = $room->round + 1;

        if ($next > $room->total_rounds || $members->count() < config('familia.min_families')) {
            $this->endGame($room);
            return;
        }

        $drawer = $members[($next - 1) % $members->count()];
        $word = $this->pickWord($room);

        $state = $room->state ?? [];
        $state['correct'] = [];
        $used = $state['used_words'] ?? [];
        $used[] = $word;
        $state['used_words'] = $used;

        $room->update([
            'round' => $next,
            'drawer_member_id' => $drawer->id,
            'word' => $word,
            'round_started_at' => now(),
            'round_ends_at' => now()->addSeconds((int) config('familia.round_seconds')),
            'state' => $state,
        ]);

        $room->load('members');
        $this->emit(new RoomUpdated($room));
        $this->emit(new ChatPosted($room->code, 'system', "Ronda {$next}/{$room->total_rounds} — dibuja la familia {$drawer->name}."));
    }

    private function endRound(FamilyRoom $room): void
    {
        $word = $room->word;
        // "Reclamamos" la ronda poniendo la palabra en null para evitar doble cierre.
        $room->update(['word' => null, 'round_ends_at' => null]);
        $this->emit(new ChatPosted($room->code, 'system', "La palabra era: {$word}"));
        $this->startNextRound($room);
    }

    private function endGame(FamilyRoom $room): void
    {
        $room->update([
            'status' => 'ended',
            'word' => null,
            'drawer_member_id' => null,
            'round_ends_at' => null,
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

    // ---------------------------------------------------------------- utilidades

    /** Transmite un evento sin dejar que una caída de Reverb rompa la petición. */
    private function emit(object $event): void
    {
        rescue(fn () => broadcast($event), report: false);
    }

    private function findRoom(string $code, bool $lock = false): ?FamilyRoom
    {
        $q = FamilyRoom::where('code', strtoupper($code));
        if ($lock) {
            $q->lockForUpdate();
        }
        return $q->first();
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

    private function pickWord(FamilyRoom $room): string
    {
        $words = config('familia.words');
        $used = $room->state['used_words'] ?? [];
        $available = array_values(array_diff($words, $used));
        if (empty($available)) {
            $available = $words; // se agotaron: reiniciamos
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
