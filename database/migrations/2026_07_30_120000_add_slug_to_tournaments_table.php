<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Columna (sin índice único todavía: SQLite no permite añadir UNIQUE inline en ALTER).
        Schema::table('tournaments', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
        });

        // 2) Backfill de torneos existentes con slugs legibles y únicos.
        $used = [];
        foreach (DB::table('tournaments')->select('id', 'name')->orderBy('id')->get() as $t) {
            $base = Str::slug($t->name);
            if ($base === '' || ctype_digit($base)) {
                $base = 'torneo-' . $t->id;
            }
            $slug = $base;
            $n = 2;
            while (in_array($slug, $used, true) || DB::table('tournaments')->where('slug', $slug)->exists()) {
                $slug = $base . '-' . $n++;
            }
            $used[] = $slug;
            DB::table('tournaments')->where('id', $t->id)->update(['slug' => $slug]);
        }

        // 3) Índice único.
        Schema::table('tournaments', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
