<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->after('score2');
        });

        DB::table('matches')->where('played', true)->update(['status' => 'finished']);
        DB::table('matches')->where('played', false)->update(['status' => 'pending']);

        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn('played');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->boolean('played')->default(false)->after('score2');
        });

        DB::table('matches')->where('status', 'finished')->update(['played' => true]);
        DB::table('matches')->whereIn('status', ['pending', 'live'])->update(['played' => false]);

        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
