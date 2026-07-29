<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('security_questions');
    }

    public function down(): void
    {
        // Not needed - old table replaced by user_security_questions
    }
};
