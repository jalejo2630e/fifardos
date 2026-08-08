<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_room_id')->constrained()->cascadeOnDelete();
            $table->string('name');              // nombre de la familia
            $table->string('token')->index();    // identifica al navegador de esa familia
            $table->unsignedTinyInteger('slot'); // 1..3
            $table->unsignedInteger('score')->default(0);
            $table->boolean('is_host')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->unique(['family_room_id', 'slot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_members');
    }
};
