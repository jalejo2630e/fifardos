<?php

namespace App\Models;

use App\Services\SportsCatalog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameMatch extends Model
{
    use HasFactory;
    protected $table = 'matches';

    protected $fillable = [
        'tournament_id',
        'round',
        'player1_id',
        'player2_id',
        'team1_id',
        'team2_id',
        'score1',
        'score2',
        'status',
        'tv_number',
        'played_at',
        'phase',
        'bracket_position',
        'stats',
        'penalties1',
        'penalties2',
        'sets',
    ];

    protected $casts = [
        'status' => 'string',
        'played_at' => 'datetime',
        'stats' => 'array',
        'sets' => 'array',
        'penalties1' => 'integer',
        'penalties2' => 'integer',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function player1(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player1_id');
    }

    public function player2(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player2_id');
    }

    public function team1(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team1_id');
    }

    public function team2(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team2_id');
    }

    public function goalScorers(): HasMany
    {
        return $this->hasMany(GoalScorer::class, 'match_id');
    }

    /** Nombre del competidor 1 (jugador o equipo) según el deporte. */
    public function competitor1Name(): ?string
    {
        if ($this->team1) return $this->team1->name;
        return $this->player1?->name;
    }

    /** Nombre del competidor 2 (jugador o equipo) según el deporte. */
    public function competitor2Name(): ?string
    {
        if ($this->team2) return $this->team2->name;
        return $this->player2?->name;
    }

    /** ID del competidor 1 (jugador o equipo) según el deporte. */
    public function competitor1Id(): ?int
    {
        return $this->team1_id ?? $this->player1_id;
    }

    /** ID del competidor 2 (jugador o equipo) según el deporte. */
    public function competitor2Id(): ?int
    {
        return $this->team2_id ?? $this->player2_id;
    }

    /** 1 si ganó el local, 2 si ganó el visitante, 0 empate (o no jugado). */
    public function outcome(): int
    {
        $sport = $this->tournament?->sport ?? 'fifa';
        return SportsCatalog::matchOutcome(
            $sport,
            $this->score1,
            $this->score2,
            $this->penalties1,
            $this->penalties2,
        );
    }

    /** Devuelve el nombre del ganador del partido (o null si no jugado/empate). */
    public function winnerName(): ?string
    {
        $outcome = $this->outcome();
        return match ($outcome) {
            1 => $this->competitor1Name(),
            2 => $this->competitor2Name(),
            default => null,
        };
    }
}
