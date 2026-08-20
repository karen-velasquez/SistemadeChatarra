<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE movimientos MODIFY categoria ENUM(
            'pago_cliente', 'anticipo_cliente', 'pago_proveedor', 'pago_camion',
            'gasto_extra', 'pago_sueldo', 'prestamo_otorgado', 'prestamo_recibido',
            'devolucion_prestamo', 'mantenimiento_camion', 'otro'
        )");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE movimientos MODIFY categoria ENUM(
            'pago_cliente', 'anticipo_cliente', 'pago_proveedor', 'pago_camion',
            'gasto_extra', 'pago_sueldo', 'prestamo_otorgado', 'prestamo_recibido',
            'devolucion_prestamo', 'otro'
        )");
    }
};
