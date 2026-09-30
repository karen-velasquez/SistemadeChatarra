<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reglas_comision2', function (Blueprint $table) {
            // La Comisión 2 deja de ser un monto fijo por tonelada y vuelve a
            // ser un porcentaje sobre el total de venta (tn x precio), como antes.
            $table->renameColumn('monto_por_tonelada', 'porcentaje');
        });
    }

    public function down(): void
    {
        Schema::table('reglas_comision2', function (Blueprint $table) {
            $table->renameColumn('porcentaje', 'monto_por_tonelada');
        });
    }
};
