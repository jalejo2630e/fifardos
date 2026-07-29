<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Marca como verificados los usuarios que ya existían al activar MustVerifyEmail,
     * para no bloquearlos. Los registros NUEVOS sí deberán verificar su email.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // No revertimos (no podemos saber quiénes estaban sin verificar).
    }
};
