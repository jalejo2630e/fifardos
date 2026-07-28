<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->string('apellido')->nullable()->after('name');
            $table->string('username')->nullable()->unique()->after('email');
            $table->string('password')->nullable()->after('preferred_team');
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn(['apellido', 'username', 'password']);
        });
    }
};
