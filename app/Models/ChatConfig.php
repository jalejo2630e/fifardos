<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatConfig extends Model
{
    protected $fillable = [
        'system_prompt',
        'forbidden_topics',
        'is_active',
        'max_tokens',
        'temperature',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_tokens' => 'integer',
        'temperature' => 'float',
    ];
}
