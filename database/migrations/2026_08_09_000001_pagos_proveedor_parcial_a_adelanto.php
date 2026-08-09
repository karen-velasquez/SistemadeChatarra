<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// En pagos a proveedores solo se manejan dos tipos: adelanto y pago final.
// Los pagos masivos antiguos se guardaban siempre como 'parcial'; los que no
// liquidaron el contrato son adelantos.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('pagos_proveedor')
            ->where('tipo_pago', 'parcial')
            ->update(['tipo_pago' => 'adelanto']);
    }

    public function down(): void
    {
        // No se revierte: 'parcial' y 'adelanto' quedan indistinguibles tras la conversión.
    }
};
