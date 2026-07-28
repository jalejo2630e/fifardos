<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_configs', function (Blueprint $table) {
            $table->id();
            $table->text('system_prompt');
            $table->text('forbidden_topics')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('max_tokens')->default(500);
            $table->float('temperature')->default(0.7);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_configs');
    }
};
