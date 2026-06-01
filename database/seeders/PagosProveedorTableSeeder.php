<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PagosProveedorTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('pagos_proveedor')->insert([
            // ─── Contrato 1 (CTR-2026-001) - Proveedor 1 ───
            // Adelanto al inicio del contrato
            [
                'uuid'               => Str::uuid(),
                'lote_pago_id'       => null,
                'contrato_id'        => 1,
                'tipo_pago'          => 'adelanto',
                'monto'              => 20000.00,
                'moneda_pago'        => 'BOB',
                'tipo_cambio'        => 1.0000,
                'fecha_pago'         => '2026-01-04',
                'metodo_pago'        => 'transferencia',
                'codigo_seguimiento' => 'TRF-PROV-2026-001',
                'cuenta_origen_id'   => null,
                'cuenta_destino_id'  => 1, // cuenta bancaria proveedor 1 (Banco Unión BOB)
                'cuenta_empresa_id'  => 2, // Banco Unión BOB empresa
                'observaciones'      => 'Adelanto 25% sobre monto total contrato CTR-2026-001',
                'created_by'         => 1,
                'updated_by'         => 1,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
            // Pago parcial a mitad del contrato
            [
                'uuid'               => Str::uuid(),
                'lote_pago_id'       => null,
                'contrato_id'        => 1,
                'tipo_pago'          => 'parcial',
                'monto'              => 35000.00,
                'moneda_pago'        => 'BOB',
                'tipo_cambio'        => 1.0000,
                'fecha_pago'         => '2026-01-20',
                'metodo_pago'        => 'transferencia',
                'codigo_seguimiento' => 'TRF-PROV-2026-002',
                'cuenta_origen_id'   => null,
                'cuenta_destino_id'  => 1,
                'cuenta_empresa_id'  => 2,
                'observaciones'      => 'Pago parcial 44% - camiones 1 y 2 en ruta',
                'created_by'         => 1,
                'updated_by'         => 1,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],

            // ─── Contrato 2 (CTR-2026-002) - Proveedor 2 en USD ───
            // Adelanto
            [
                'uuid'               => Str::uuid(),
                'lote_pago_id'       => null,
                'contrato_id'        => 2,
                'tipo_pago'          => 'adelanto',
                'monto'              => 5000.00,
                'moneda_pago'        => 'USD',
                'tipo_cambio'        => 6.9600,
                'fecha_pago'         => '2026-02-03',
                'metodo_pago'        => 'transferencia',
                'codigo_seguimiento' => 'TRF-PROV-USD-2026-001',
                'cuenta_origen_id'   => null,
                'cuenta_destino_id'  => 2, // cuenta bancaria proveedor 2 (Bisa USD)
                'cuenta_empresa_id'  => 3, // Banco Bisa USD empresa
                'observaciones'      => 'Adelanto USD - contrato internacional CTR-2026-002',
                'created_by'         => 1,
                'updated_by'         => 1,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
            // Pago parcial
            [
                'uuid'               => Str::uuid(),
                'lote_pago_id'       => null,
                'contrato_id'        => 2,
                'tipo_pago'          => 'parcial',
                'monto'              => 8000.00,
                'moneda_pago'        => 'USD',
                'tipo_cambio'        => 6.9600,
                'fecha_pago'         => '2026-02-25',
                'metodo_pago'        => 'transferencia',
                'codigo_seguimiento' => 'TRF-PROV-USD-2026-002',
                'cuenta_origen_id'   => null,
                'cuenta_destino_id'  => 2,
                'cuenta_empresa_id'  => 3,
                'observaciones'      => 'Pago parcial USD - envío internacional confirmado',
                'created_by'         => 1,
                'updated_by'         => 1,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],

            // ─── Contrato 3 (CTR-2026-003) - Proveedor 1 ───
            // Adelanto en efectivo
            [
                'uuid'               => Str::uuid(),
                'lote_pago_id'       => null,
                'contrato_id'        => 3,
                'tipo_pago'          => 'adelanto',
                'monto'              => 12000.00,
                'moneda_pago'        => 'BOB',
                'tipo_cambio'        => 1.0000,
                'fecha_pago'         => '2026-03-17',
                'metodo_pago'        => 'efectivo',
                'codigo_seguimiento' => null,
                'cuenta_origen_id'   => null,
                'cuenta_destino_id'  => null,
                'cuenta_empresa_id'  => 1, // Caja Principal BOB
                'observaciones'      => 'Adelanto en efectivo - contrato CTR-2026-003 Cochabamba-Trinidad',
                'created_by'         => 1,
                'updated_by'         => 1,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
        ]);
    }
}
