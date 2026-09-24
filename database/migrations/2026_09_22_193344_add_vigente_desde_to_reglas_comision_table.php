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
        Schema::table('reglas_comision', function (Blueprint $table) {
            // Primer día del mes/año desde el cual rige este monto. Puede haber varias
            // filas para la misma combinación cliente+empresa, una por mes de inicio;
            // se usa la vigente según la fecha de entrega de cada venta.
            $table->date('vigente_desde')->nullable()->after('monto_por_tonelada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reglas_comision', function (Blueprint $table) {
            $table->dropColumn('vigente_desde');
        });
    }
};
