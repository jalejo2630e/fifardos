<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->integer('round');
            $table->foreignId('player1_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('player2_id')->constrained('players')->cascadeOnDelete();
            $table->integer('score1')->nullable();
            $table->integer('score2')->nullable();
            $table->boolean('played')->default(false);
            $table->integer('tv_number')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
