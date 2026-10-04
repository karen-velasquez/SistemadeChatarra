<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            // TC aproximado del día del contrato (solo aplica si moneda != BOB).
            // Sirve de referencia para convertir toda la línea de venta a bolivianos
            // en el Excel, y para comparar contra el TC real de cada pago posterior.
            $table->decimal('tipo_cambio', 12, 4)->nullable()->after('moneda');
        });
    }

    public function down(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            $table->dropColumn('tipo_cambio');
        });
    }
};
