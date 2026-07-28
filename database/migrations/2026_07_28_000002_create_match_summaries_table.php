<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('tournament_id')->nullable()->constrained('tournaments')->nullOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->text('summary_text');
            $table->text('embedding')->nullable();
            $table->timestamps();

            $table->index('player_id');
            $table->index('tournament_id');
            $table->index(['player_id', 'tournament_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_summaries');
    }
};
