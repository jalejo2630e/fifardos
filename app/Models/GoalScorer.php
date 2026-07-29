<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalScorer extends Model
{
    protected $fillable = [
        'match_id',
        'player_id',
        'goals',
        'minutes',
    ];

    protected $casts = [
        'minutes' => 'array',
        'goals' => 'integer',
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
