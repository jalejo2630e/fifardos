<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->foreignId('team1_id')->nullable()->after('player2_id')
                ->constrained('teams')->cascadeOnDelete();
            $table->foreignId('team2_id')->nullable()->after('team1_id')
                ->constrained('teams')->cascadeOnDelete();
            $table->json('sets')->nullable()->after('penalties2');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team1_id');
            $table->dropConstrainedForeignId('team2_id');
            $table->dropColumn('sets');
        });
    }
};
