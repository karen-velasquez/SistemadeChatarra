<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lotes_pago', function (Blueprint $table) {
            // Lotes existentes ya fueron reflejados en tesorería al crearse: quedan
            // 'confirmado' por defecto en el backfill, ver DB::table()->update() abajo.
            $table->string('estado')->default('pendiente')->after('tipo');
        });

        DB::table('lotes_pago')->update(['estado' => 'confirmado']);
    }

    public function down(): void
    {
        Schema::table('lotes_pago', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }
};
