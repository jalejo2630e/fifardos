<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FamilyRoom extends Model
{
    protected $fillable = [
        'code', 'game', 'status', 'host_token', 'round', 'total_rounds',
        'drawer_member_id', 'word', 'round_started_at', 'round_ends_at', 'state',
        'playlist', 'playlist_pos',
    ];

    protected $casts = [
        'state' => 'array',
        'playlist' => 'array',
        'round_started_at' => 'datetime',
        'round_ends_at' => 'datetime',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(FamilyMember::class)->orderBy('slot');
    }

    public function drawer(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class, 'drawer_member_id');
    }

    /**
     * Snapshot público (sin datos secretos: palabra, respuestas privadas, etc.).
     */
    public function publicSnapshot(): array
    {
        $this->loadMissing('members');

        return [
            'code' => $this->code,
            'game' => $this->game,
            'status' => $this->status,
            'round' => $this->round,
            'total_rounds' => $this->total_rounds,
            'playlist' => $this->playlist ?? [],
            'playlist_pos' => (int) $this->playlist_pos,
            'kick' => $this->state['kick'] ?? null,   // expulsión pendiente (con cuenta regresiva)
            'round_ends_at' => optional($this->round_ends_at)->toIso8601String(),
            'game_state' => $this->publicGameState(),
            'members' => $this->members->map(fn (FamilyMember $m) => [
                'id' => $m->id,
                'name' => $m->name,
                'slot' => $m->slot,
                'score' => $m->score,
                'game_score' => (int) $m->game_score,
                'is_host' => (bool) $m->is_host,
                'online' => $m->last_seen_at && $m->last_seen_at->gt(now()->subSeconds(30)),
            ])->values(),
        ];
    }

    /**
     * Vista pública del estado del juego según fase. Durante 'play' NO expone las
     * respuestas privadas (dibujo/palabra, opción elegida, textos de tutti frutti).
     */
    public function publicGameState(): array
    {
        $s = $this->state ?? [];
        $phase = $s['phase'] ?? null;               // play | reveal
        $out = ['phase' => $phase];

        if ($phase === 'reveal') {
            $out['reveal'] = $s['reveal'] ?? null;  // el resultado sí es público
        }
        if ($phase === 'gameover' || $phase === 'ended') {
            $out['result'] = $s['result'] ?? null;  // podio de la partida / total de la tanda
        }

        if ($this->game === 'pictionary') {
            $out['drawer_member_id'] = $this->drawer_member_id;
            if ($phase === 'play') {
                $out['word_length'] = filled($this->word) ? mb_strlen($this->word) : null;
                $out['correct'] = $s['correct'] ?? [];
            }
        } elseif ($this->game === 'trivia') {
            if ($phase === 'play') {
                $out['question'] = $s['question'] ?? null;
                $out['options'] = $s['options'] ?? [];
                $out['answered'] = array_map('intval', array_keys($s['answers'] ?? []));
            }
        } elseif ($this->game === 'tuttifrutti') {
            if ($phase === 'play') {
                $out['letter'] = $s['letter'] ?? null;
                $out['categories'] = $s['categories'] ?? [];
                $out['submitted'] = array_map('intval', array_keys($s['submissions'] ?? []));
            } elseif ($phase === 'validate') {
                $out['letter'] = $s['letter'] ?? null;
                $out['categories'] = $s['categories'] ?? [];
                $out['entries'] = $s['entries'] ?? [];       // respuestas de todas las familias (públicas)
                $out['votes'] = (object) ($s['votes'] ?? []); // { voterId: ["owner:cat", ...] }
            }
        } elseif ($this->game === 'hangman') {
            if ($phase === 'play') {
                $guessed = $s['guessed'] ?? [];
                $masked = [];
                foreach (mb_str_split((string) $this->word) as $ch) {
                    if ($ch === ' ') {
                        $masked[] = ' ';
                    } elseif (in_array(mb_strtolower($ch), $guessed, true)) {
                        $masked[] = $ch;   // letra ya adivinada → visible
                    } else {
                        $masked[] = '';    // oculta
                    }
                }
                $out['masked'] = $masked;
                $out['guessed'] = $guessed;
                $out['misses'] = (int) ($s['misses'] ?? 0);
                $out['max_misses'] = (int) config('familia.hangman.max_misses');
                $out['turn'] = $s['turn'] ?? null;   // de quién es el turno
                $out['hint'] = $s['hint'] ?? null;   // categoría (pista)
            }
        } elseif ($this->game === 'memoria') {
            if ($phase === 'play') {
                $cards = $s['cards'] ?? [];
                $flipped = $s['flipped'] ?? [];
                $found = $s['found'] ?? [];
                $out['cards'] = array_map(function ($c) use ($flipped, $found) {
                    $up = in_array($c['id'], $flipped, true) || in_array($c['id'], $found, true);
                    return [
                        'id' => $c['id'],
                        'found' => in_array($c['id'], $found, true),
                        'up' => $up,
                        'value' => $up ? $c['value'] : null,   // oculta el resto
                    ];
                }, $cards);
                $out['turn'] = $s['turn'] ?? null;
                $out['pairs_total'] = intdiv(count($cards), 2);
                $out['pairs_found'] = intdiv(count($found), 2);
                $out['resolve_at'] = $s['resolve_at'] ?? null;
            }
        }

        return $out;
    }
}
