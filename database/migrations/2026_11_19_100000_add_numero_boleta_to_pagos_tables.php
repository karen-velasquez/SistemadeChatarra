<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tablas = ['pagos_proveedor', 'pagos_camion', 'pagos_cliente'];

    public function up(): void
    {
        foreach ($this->tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                // Número de la boleta de depósito/transferencia (5 a 8 dígitos).
                $table->string('numero_boleta', 8)->nullable()->after('codigo_seguimiento')->index();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropIndex([ 'numero_boleta' ]);
                $table->dropColumn('numero_boleta');
            });
        }
    }
};
