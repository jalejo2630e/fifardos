<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SportRuleDefinition extends Model
{
    protected $fillable = [
        'sport',
        'key',
        'label',
        'label_en',
        'type',
        'default',
        'group',
        'options',
        'min',
        'max',
        'note',
        'note_en',
        'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'min' => 'integer',
        'max' => 'integer',
        'sort_order' => 'integer',
    ];
}
