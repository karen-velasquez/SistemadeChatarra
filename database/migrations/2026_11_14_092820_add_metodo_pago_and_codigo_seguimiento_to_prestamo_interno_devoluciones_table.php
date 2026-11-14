<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prestamo_interno_devoluciones', function (Blueprint $table) {
            $table->string('metodo_pago')->nullable()->after('concepto');
            $table->string('codigo_seguimiento', 100)->nullable()->after('metodo_pago');
        });
    }

    public function down(): void
    {
        Schema::table('prestamo_interno_devoluciones', function (Blueprint $table) {
            $table->dropColumn(['metodo_pago', 'codigo_seguimiento']);
        });
    }
};
