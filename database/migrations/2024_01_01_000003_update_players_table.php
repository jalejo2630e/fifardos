<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->string('psn_id')->nullable()->after('name');
            $table->string('email')->nullable()->after('psn_id');
            $table->string('preferred_team')->nullable()->after('email');
            $table->unique(['tournament_id', 'psn_id']);
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropUnique(['tournament_id', 'psn_id']);
            $table->dropColumn(['psn_id', 'email', 'preferred_team']);
        });
    }
};
