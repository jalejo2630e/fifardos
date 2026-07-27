<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentPrize extends Model
{
    protected $fillable = [
        'tournament_id',
        'position',
        'label',
        'amount',
        'perks',
        'icon',
        'is_featured',
    ];

    protected $casts = [
        'perks' => 'array',
        'is_featured' => 'boolean',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }
}
