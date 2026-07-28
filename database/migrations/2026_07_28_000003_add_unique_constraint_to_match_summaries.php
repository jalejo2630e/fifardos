<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('match_summaries', function (Blueprint $table) {
            $table->unique(['player_id', 'period_start', 'period_end'], 'match_summaries_player_period_unique');
        });
    }

    public function down(): void
    {
        Schema::table('match_summaries', function (Blueprint $table) {
            $table->dropUnique('match_summaries_player_period_unique');
        });
    }
};
