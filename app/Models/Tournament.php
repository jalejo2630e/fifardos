<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'name',
        'consoles_count',
        'minutes_per_match',
        'status',
        'format',
        'home_and_away',
        'color',
        'max_players',
        'finished_at',
        'reminder_at',
        'reminder_email',
        'reminder_sent_at',
    ];

    protected $casts = [
        'home_and_away' => 'boolean',
        'finished_at' => 'datetime',
        'reminder_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
    ];

    /**
     * Estima la duración total del torneo en minutos: partidos del round-robin
     * (liga o fase de grupos, con ida y vuelta si aplica) + eliminatorias (solo en
     * formato groups_knockout), repartidos en paralelo entre las consolas/canchas.
     */
    public static function estimateMinutes(
        int $players,
        int $consoles,
        int $minutesPerMatch,
        string $format = 'groups_knockout',
        bool $homeAndAway = false,
    ): int {
        if ($players < 2) return 0;

        $consoles = max(1, $consoles);
        $minutesPerMatch = max(1, $minutesPerMatch);

        $group = intdiv($players * ($players - 1), 2);
        if ($homeAndAway) $group *= 2;

        $knockout = 0;
        if ($format === 'groups_knockout') {
            // Réplica del top de eliminatorias (ver TournamentController::autoGenerateKnockout)
            $top = $players <= 4 ? 4 : ($players <= 8 ? 8 : 16);
            $top = min($top, $players);
            $top = (int) pow(2, (int) floor(log($top, 2)));
            if ($top < 2) $top = 2;
            $knockout = $top >= 4 ? $top : 1;
        }

        $total = $group + $knockout;
        $slots = (int) ceil($total / $consoles);

        return $slots * $minutesPerMatch;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class);
    }

    public function prizes(): HasMany
    {
        return $this->hasMany(TournamentPrize::class);
    }
}
