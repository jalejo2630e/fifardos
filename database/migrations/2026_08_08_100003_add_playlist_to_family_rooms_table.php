<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_rooms', function (Blueprint $table) {
            $table->json('playlist')->nullable();                 // secuencia de juegos elegida
            $table->unsignedInteger('playlist_pos')->default(0);  // índice del juego actual
        });
    }

    public function down(): void
    {
        Schema::table('family_rooms', function (Blueprint $table) {
            $table->dropColumn(['playlist', 'playlist_pos']);
        });
    }
};
