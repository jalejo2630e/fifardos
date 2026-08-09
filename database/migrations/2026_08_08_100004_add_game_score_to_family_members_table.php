<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_members', function (Blueprint $table) {
            // Puntos del juego actual (se reinicia cada partida); 'score' es el total de la tanda.
            $table->unsignedInteger('game_score')->default(0)->after('score');
        });
    }

    public function down(): void
    {
        Schema::table('family_members', function (Blueprint $table) {
            $table->dropColumn('game_score');
        });
    }
};
