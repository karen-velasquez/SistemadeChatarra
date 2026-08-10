<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// La cuenta de origen de un pago a camión es una cuenta de tesorería de la
// empresa, pero la FK apuntaba a `cuentas_bancarias` (cuentas de terceros).
// El controlador valida contra `cuentas_empresa` y el modelo relaciona con
// CuentaEmpresa, así que al guardar reventaba la restricción.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos_camion', function (Blueprint $table) {
            $table->dropForeign('pagos_camion_cuenta_origen_id_foreign');
            $table->foreign('cuenta_origen_id')
                ->references('id')->on('cuentas_empresa')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pagos_camion', function (Blueprint $table) {
            $table->dropForeign('pagos_camion_cuenta_origen_id_foreign');
            $table->foreign('cuenta_origen_id')
                ->references('id')->on('cuentas_bancarias')
                ->nullOnDelete();
        });
    }
};
