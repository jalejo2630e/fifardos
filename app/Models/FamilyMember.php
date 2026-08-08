<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyMember extends Model
{
    protected $fillable = [
        'family_room_id', 'name', 'token', 'slot', 'score', 'is_host', 'last_seen_at',
    ];

    protected $casts = [
        'is_host' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(FamilyRoom::class, 'family_room_id');
    }
}
