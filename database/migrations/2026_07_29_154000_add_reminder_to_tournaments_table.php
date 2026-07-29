<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->timestamp('reminder_at')->nullable()->after('finished_at');
            $table->string('reminder_email')->nullable()->after('reminder_at');
            $table->timestamp('reminder_sent_at')->nullable()->after('reminder_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn(['reminder_at', 'reminder_email', 'reminder_sent_at']);
        });
    }
};
