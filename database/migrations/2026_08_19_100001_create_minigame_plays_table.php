<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registro histórico (append-only) de la actividad de minijuegos.
     *
     * Las salas (family_rooms) se purgan a las pocas horas, así que sin esta
     * bitácora no habría forma de reportar cuánto se juega cada minijuego.
     * A propósito NO lleva foreign key hacia family_rooms: debe sobrevivir al
     * borrado de la sala. Nunca se purga.
     */
    public function up(): void
    {
        Schema::create('minigame_plays', function (Blueprint $table) {
            $table->id();
            $table->string('type', 12);                       // 'lobby' (sala creada) | 'game' (partida jugada)
            $table->unsignedBigInteger('room_id')->nullable(); // id de la sala (sin FK: sobrevive al prune)
            $table->string('room_code', 8)->nullable();
            $table->string('game', 20)->nullable();           // pictionary | trivia | tuttifrutti | hangman | memoria
            $table->string('trivia_difficulty', 10)->nullable();
            $table->unsignedSmallInteger('players')->nullable(); // participantes al iniciar la partida
            $table->timestamps();

            $table->index('type');
            $table->index('game');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('minigame_plays');
    }
};
