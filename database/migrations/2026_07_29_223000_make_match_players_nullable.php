<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Los partidos de eliminatorias se crean como placeholders (final, semis, cuartos)
 * antes de conocer a los clasificados: en esos casos player1_id/player2_id quedan
 * en NULL hasta que la fase previa termina. Las columnas eran NOT NULL, lo que
 * rompía la generación del bracket en MySQL. Las hacemos nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->unsignedBigInteger('player1_id')->nullable()->change();
            $table->unsignedBigInteger('player2_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->unsignedBigInteger('player1_id')->nullable(false)->change();
            $table->unsignedBigInteger('player2_id')->nullable(false)->change();
        });
    }
};
