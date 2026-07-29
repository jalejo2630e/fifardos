<?php

namespace App\Models;

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
    ];

    protected $casts = [
        'status' => 'string',
        'played_at' => 'datetime',
        'stats' => 'array',
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

    public function goalScorers(): HasMany
    {
        return $this->hasMany(GoalScorer::class, 'match_id');
    }
}
