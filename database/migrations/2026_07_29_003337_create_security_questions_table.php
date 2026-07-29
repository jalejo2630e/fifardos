<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('question_1');
            $table->string('answer_1');
            $table->string('question_2');
            $table->string('answer_2');
            $table->string('question_3');
            $table->string('answer_3');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_questions');
    }
};
