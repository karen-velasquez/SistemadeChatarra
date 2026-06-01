<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PagosClienteTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('pagos_cliente')->insert([
            // ─── Tramo 1 (Nacional, entregado) — Cliente 1 recibió chatarra ───
            // Adelanto del cliente antes de la entrega
            [
                'uuid'               => Str::uuid(),
                'lote_pago_id'       => null,
                'tramo_id'           => 1,
                'tipo_pago'          => 'adelanto',
                'monto'              => 7000.00,
                'moneda_pago'        => 'BOB',
                'tipo_cambio'        => 1.0000,
                'fecha_pago'         => '2026-01-06',
                'metodo_pago'        => 'transferencia',
                'codigo_seguimiento' => 'CLI-TRF-2026-001',
                'cuenta_origen_id'   => null,
                'cuenta_destino_id'  => null,
                'cuenta_empresa_id'  => 2, // Banco Unión BOB empresa
                'observaciones'      => 'Adelanto cliente 1 por tramo Santa Cruz - La Paz (18.35 ton)',
                'deleted_by'         => null,
                'created_by'         => 1,
                'updated_by'         => 1,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
            // Pago final tras la entrega
            [
                'uuid'               => Str::uuid(),
                'lote_pago_id'       => null,
                'tramo_id'           => 1,
                'tipo_pago'          => 'pago_final',
                'monto'              => 7924.00,
                'moneda_pago'        => 'BOB',
                'tipo_cambio'        => 1.0000,
                'fecha_pago'         => '2026-01-10',
                'metodo_pago'        => 'qr',
                'codigo_seguimiento' => 'CLI-QR-2026-001',
                'cuenta_origen_id'   => null,
                'cuenta_destino_id'  => null,
                'cuenta_empresa_id'  => 2,
                // 18.2 ton * 820 BOB = 14924, menos 7000 adelanto = 7924
                'observaciones'      => 'Pago final - 18.2 ton x 820 BOB/ton = 14,924 BOB total',
                'deleted_by'         => null,
                'created_by'         => 1,
                'updated_by'         => 1,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],

            // ─── Tramo 2 (Nacional, en ruta) — Cliente 1 ───
            // Solo adelanto, aún en ruta
            [
                'uuid'               => Str::uuid(),
                'lote_pago_id'       => null,
                'tramo_id'           => 2,
                'tipo_pago'          => 'adelanto',
                'monto'              => 8000.00,
                'moneda_pago'        => 'BOB',
                'tipo_cambio'        => 1.0000,
                'fecha_pago'         => '2026-01-12',
                'metodo_pago'        => 'transferencia',
                'codigo_seguimiento' => 'CLI-TRF-2026-002',
                'cuenta_origen_id'   => null,
                'cuenta_destino_id'  => null,
                'cuenta_empresa_id'  => 2,
                'observaciones'      => 'Adelanto cliente 1 - tramo Oruro-Cochabamba en ruta',
                'deleted_by'         => null,
                'created_by'         => 1,
                'updated_by'         => 1,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],

            // ─── Tramo 4 (Internacional, en ruta) — Cliente 2 ───
            // Adelanto internacional en USD
            [
                'uuid'               => Str::uuid(),
                'lote_pago_id'       => null,
                'tramo_id'           => 4,
                'tipo_pago'          => 'adelanto',
                'monto'              => 1000.00,
                'moneda_pago'        => 'USD',
                'tipo_cambio'        => 6.9600,
                'fecha_pago'         => '2026-02-07',
                'metodo_pago'        => 'transferencia',
                'codigo_seguimiento' => 'CLI-USD-2026-001',
                'cuenta_origen_id'   => null,
                'cuenta_destino_id'  => null,
                'cuenta_empresa_id'  => 3, // Banco Bisa USD empresa
                'observaciones'      => 'Adelanto USD - envío internacional Santa Cruz a São Paulo',
                'deleted_by'         => null,
                'created_by'         => 1,
                'updated_by'         => 1,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],

            // ─── Tramo 5 (Internacional, transbordado) — Cliente 2 ───
            // Pago parcial
            [
                'uuid'               => Str::uuid(),
                'lote_pago_id'       => null,
                'tramo_id'           => 5,
                'tipo_pago'          => 'parcial',
                'monto'              => 1600.00,
                'moneda_pago'        => 'USD',
                'tipo_cambio'        => 6.9600,
                'fecha_pago'         => '2026-02-18',
                'metodo_pago'        => 'transferencia',
                'codigo_seguimiento' => 'CLI-USD-2026-002',
                'cuenta_origen_id'   => null,
                'cuenta_destino_id'  => null,
                'cuenta_empresa_id'  => 3,
                // 24.3 ton * 130 USD = 3159 USD total, pago parcial 1600
                'observaciones'      => 'Pago parcial USD - 24.3 ton x 130 USD, transbordado frontera',
                'deleted_by'         => null,
                'created_by'         => 1,
                'updated_by'         => 1,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],

            // ─── Tramo 6 (Nacional, en ruta) — Cliente 3 ───
            // Adelanto
            [
                'uuid'               => Str::uuid(),
                'lote_pago_id'       => null,
                'tramo_id'           => 6,
                'tipo_pago'          => 'adelanto',
                'monto'              => 5000.00,
                'moneda_pago'        => 'BOB',
                'tipo_cambio'        => 1.0000,
                'fecha_pago'         => '2026-03-20',
                'metodo_pago'        => 'efectivo',
                'codigo_seguimiento' => null,
                'cuenta_origen_id'   => null,
                'cuenta_destino_id'  => null,
                'cuenta_empresa_id'  => 1, // Caja Principal BOB
                'observaciones'      => 'Adelanto efectivo cliente 3 - tramo Cochabamba-Trinidad',
                'deleted_by'         => null,
                'created_by'         => 1,
                'updated_by'         => 1,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
        ]);
    }
}
