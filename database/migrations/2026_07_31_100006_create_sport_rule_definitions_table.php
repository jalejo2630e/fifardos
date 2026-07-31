<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sport_rule_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('sport', 50)->index();
            $table->string('key', 100);
            $table->string('label', 255);
            $table->string('label_en', 255)->nullable();
            $table->string('type', 20)->default('boolean'); // boolean | number | select
            $table->string('default', 50)->nullable();
            $table->string('group', 50)->default('general');
            $table->json('options')->nullable();
            $table->integer('min')->nullable();
            $table->integer('max')->nullable();
            $table->string('note', 255)->nullable();
            $table->string('note_en', 255)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['sport', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sport_rule_definitions');
    }
};
