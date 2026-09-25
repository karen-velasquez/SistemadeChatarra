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
        Schema::table('reglas_costo_adicional', function (Blueprint $table) {
            // Rango de vigencia: puede haber varias reglas para la misma combinación
            // cliente+empresa, una por rango; se usa la vigente según la fecha de
            // entrega de cada venta. Ambos extremos nullable = sin límite en ese lado.
            $table->date('fecha_inicio')->nullable()->after('monto_por_tramo');
            $table->date('fecha_fin')->nullable()->after('fecha_inicio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reglas_costo_adicional', function (Blueprint $table) {
            $table->dropColumn(['fecha_inicio', 'fecha_fin']);
        });
    }
};
