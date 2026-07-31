<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->string('rule_key', 100);
            $table->string('value', 50)->nullable();
            $table->timestamps();

            $table->unique(['tournament_id', 'rule_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_rules');
    }
};
