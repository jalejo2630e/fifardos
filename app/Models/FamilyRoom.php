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
    ];

    protected $casts = [
        'state' => 'array',
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
     * Snapshot público de la sala (SIN la palabra secreta) para transmitir a todos.
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
            'drawer_member_id' => $this->drawer_member_id,
            'has_word' => filled($this->word),
            'word_length' => filled($this->word) ? mb_strlen($this->word) : null,
            'round_ends_at' => optional($this->round_ends_at)->toIso8601String(),
            'state' => $this->state ?? [],
            'members' => $this->members->map(fn (FamilyMember $m) => [
                'id' => $m->id,
                'name' => $m->name,
                'slot' => $m->slot,
                'score' => $m->score,
                'is_host' => (bool) $m->is_host,
                'online' => $m->last_seen_at && $m->last_seen_at->gt(now()->subSeconds(30)),
            ])->values(),
        ];
    }
}
