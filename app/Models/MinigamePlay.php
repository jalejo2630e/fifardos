<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Bitácora histórica de minijuegos (append-only). Ver migración
 * create_minigame_plays_table. Alimenta los reportes del panel admin.
 */
class MinigamePlay extends Model
{
    protected $fillable = [
        'type', 'room_id', 'room_code', 'game', 'trivia_difficulty', 'players',
    ];

    protected $casts = [
        'players' => 'integer',
    ];

    /** Registra la creación de una sala (lobby). */
    public static function logLobby(FamilyRoom $room): void
    {
        static::create([
            'type' => 'lobby',
            'room_id' => $room->id,
            'room_code' => $room->code,
        ]);
    }

    /** Registra el inicio de una partida de un minijuego concreto. */
    public static function logGame(FamilyRoom $room, int $players): void
    {
        static::create([
            'type' => 'game',
            'room_id' => $room->id,
            'room_code' => $room->code,
            'game' => $room->game,
            'trivia_difficulty' => $room->game === 'trivia' ? ($room->trivia_difficulty ?? 'facil') : null,
            'players' => $players,
        ]);
    }
}
