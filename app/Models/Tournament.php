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
        'status',
        'color',
        'max_players',
        'finished_at',
        'reminder_at',
        'reminder_email',
        'reminder_sent_at',
    ];

    protected $casts = [
        'finished_at' => 'datetime',
        'reminder_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
    ];

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
