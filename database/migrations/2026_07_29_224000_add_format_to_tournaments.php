<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Formato de torneo:
 *  - format: 'groups_knockout' (fase de grupos + eliminatorias) | 'league' (liga a una rueda)
 *  - home_and_away: si el round-robin (liga o fase de grupos) se juega ida y vuelta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->string('format')->default('groups_knockout')->after('status');
            $table->boolean('home_and_away')->default(false)->after('format');
        });
    }

    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn(['format', 'home_and_away']);
        });
    }
};
