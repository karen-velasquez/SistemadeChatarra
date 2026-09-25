<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // vigente_desde pasa a representar la fecha de inicio de vigencia,
        // y se le suma fecha_fin. El rename real a "fecha_inicio" ocurre en
        // la migración 2026_09_24_092504_rename_vigente_desde_to_fecha_inicio.
        Schema::table('reglas_comision', function (Blueprint $table) {
            $table->date('fecha_fin')->nullable()->after('vigente_desde');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reglas_comision', function (Blueprint $table) {
            $table->dropColumn('fecha_fin');
        });
    }
};
