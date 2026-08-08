<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8)->unique();
            $table->string('game')->default('pictionary');
            $table->string('status')->default('lobby'); // lobby | playing | ended
            $table->string('host_token')->nullable();
            $table->unsignedInteger('round')->default(0);
            $table->unsignedInteger('total_rounds')->default(6);
            $table->foreignId('drawer_member_id')->nullable();
            $table->string('word')->nullable();          // palabra secreta de la ronda actual
            $table->timestamp('round_started_at')->nullable();
            $table->timestamp('round_ends_at')->nullable();
            $table->json('state')->nullable();           // datos varios (aciertos de la ronda, etc.)
            $table->timestamps();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_rooms');
    }
};
