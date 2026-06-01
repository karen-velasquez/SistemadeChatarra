<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PagosCamionTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('pagos_camion')->insert([
            // ─── Contrato 1 - Camión 1 (contrato_camion_id=1, monto_acordado=15000 BOB) ───
            // Adelanto inicial
            [
                'uuid'               => Str::uuid(),
                'lote_pago_id'       => null,
                'contrato_camion_id' => 1,
                'tipo_pago'          => 'adelanto',
                'monto'              => 5000.00,
                'moneda_pago'        => 'BOB',
                'tipo_cambio'        => 1.0000,
                'fecha_pago'         => '2026-01-05',
                'receptor_type'      => 'App\Models\OperadorTransporte',
                'receptor_id'        => 1,
                'cuenta_origen_id'   => null,
                'cuenta_destino_id'  => 3, // cuenta operador 1 (BNB BOB)
                'cuenta_empresa_id'  => 2, // Banco Unión BOB empresa
                'metodo_pago'        => 'transferencia',
                'codigo_seguimiento' => 'TRF-2026-0001',
                'observaciones'      => 'Adelanto 33% para inicio de viaje Santa Cruz - La Paz',
                'created_by'         => 1,
                'updated_by'         => 1,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
            // Pago final (entregado)
            [
                'uuid'               => Str::uuid(),
                'lote_pago_id'       => null,
                'contrato_camion_id' => 1,
                'tipo_pago'          => 'pago_final',
                'monto'              => 10000.00,
                'moneda_pago'        => 'BOB',
                'tipo_cambio'        => 1.0000,
                'fecha_pago'         => '2026-01-10',
                'receptor_type'      => 'App\Models\OperadorTransporte',
                'receptor_id'        => 1,
                'cuenta_origen_id'   => null,
                'cuenta_destino_id'  => 3, // cuenta operador 1
                'cuenta_empresa_id'  => 2, // Banco Unión BOB empresa
                'metodo_pago'        => 'transferencia',
                'codigo_seguimiento' => 'TRF-2026-0002',
                'observaciones'      => 'Pago final flete - carga entregada',
                'created_by'         => 1,
                'updated_by'         => 1,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],

            // ─── Contrato 1 - Camión 2 (contrato_camion_id=2, monto_acordado=18000 BOB) ───
            // Solo adelanto, pendiente
            [
                'uuid'               => Str::uuid(),
                'lote_pago_id'       => null,
                'contrato_camion_id' => 2,
                'tipo_pago'          => 'adelanto',
                'monto'              => 6000.00,
                'moneda_pago'        => 'BOB',
                'tipo_cambio'        => 1.0000,
                'fecha_pago'         => '2026-01-10',
                'receptor_type'      => 'App\Models\OperadorTransporte',
                'receptor_id'        => 1,
                'cuenta_origen_id'   => null,
                'cuenta_destino_id'  => 3,
                'cuenta_empresa_id'  => 1, // Caja Principal BOB
                'metodo_pago'        => 'efectivo',
                'codigo_seguimiento' => null,
                'observaciones'      => 'Adelanto para viaje Oruro - Cochabamba',
                'created_by'         => 1,
                'updated_by'         => 1,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],

            // ─── Contrato 1 - Camión 3 (contrato_camion_id=3, monto_acordado=14000 BOB) ───
            // Adelanto
            [
                'uuid'               => Str::uuid(),
                'lote_pago_id'       => null,
                'contrato_camion_id' => 3,
                'tipo_pago'          => 'adelanto',
                'monto'              => 4000.00,
                'moneda_pago'        => 'BOB',
                'tipo_cambio'        => 1.0000,
                'fecha_pago'         => '2026-01-15',
                'receptor_type'      => 'App\Models\OperadorTransporte',
                'receptor_id'        => 3,
                'cuenta_origen_id'   => null,
                'cuenta_destino_id'  => 4, // cuenta operador 3 (Unión BOB)
                'cuenta_empresa_id'  => 2, // Banco Unión BOB empresa
                'metodo_pago'        => 'transferencia',
                'codigo_seguimiento' => 'TRF-2026-0003',
                'observaciones'      => 'Adelanto para MARIA CHOQUE - Potosí a Cochabamba',
                'created_by'         => 1,
                'updated_by'         => 1,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],

            // ─── Contrato 2 - Camión 1 Internacional (contrato_camion_id=4, monto_acordado=2800 USD) ───
            // Adelanto en USD
            [
                'uuid'               => Str::uuid(),
                'lote_pago_id'       => null,
                'contrato_camion_id' => 4,
                'tipo_pago'          => 'adelanto',
                'monto'              => 1000.00,
                'moneda_pago'        => 'USD',
                'tipo_cambio'        => 6.9600,
                'fecha_pago'         => '2026-02-05',
                'receptor_type'      => 'App\Models\OperadorTransporte',
                'receptor_id'        => 1,
                'cuenta_origen_id'   => null,
                'cuenta_destino_id'  => 3,
                'cuenta_empresa_id'  => 3, // Banco Bisa USD empresa
                'metodo_pago'        => 'transferencia',
                'codigo_seguimiento' => 'TRF-INT-2026-0001',
                'observaciones'      => 'Adelanto USD - Santa Cruz a São Paulo',
                'created_by'         => 1,
                'updated_by'         => 1,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],

            // ─── Contrato 2 - Camión 2 Internacional (contrato_camion_id=5, monto_acordado=3200 USD) ───
            // Adelanto
            [
                'uuid'               => Str::uuid(),
                'lote_pago_id'       => null,
                'contrato_camion_id' => 5,
                'tipo_pago'          => 'adelanto',
                'monto'              => 1200.00,
                'moneda_pago'        => 'USD',
                'tipo_cambio'        => 6.9600,
                'fecha_pago'         => '2026-02-10',
                'receptor_type'      => 'App\Models\OperadorTransporte',
                'receptor_id'        => 3,
                'cuenta_origen_id'   => null,
                'cuenta_destino_id'  => 4,
                'cuenta_empresa_id'  => 3, // Banco Bisa USD empresa
                'metodo_pago'        => 'qr',
                'codigo_seguimiento' => 'QR-INT-2026-0001',
                'observaciones'      => 'Adelanto USD - La Paz a Buenos Aires',
                'created_by'         => 1,
                'updated_by'         => 1,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
            // Flete parcial
            [
                'uuid'               => Str::uuid(),
                'lote_pago_id'       => null,
                'contrato_camion_id' => 5,
                'tipo_pago'          => 'flete',
                'monto'              => 1000.00,
                'moneda_pago'        => 'USD',
                'tipo_cambio'        => 6.9600,
                'fecha_pago'         => '2026-02-20',
                'receptor_type'      => 'App\Models\OperadorTransporte',
                'receptor_id'        => 3,
                'cuenta_origen_id'   => null,
                'cuenta_destino_id'  => 4,
                'cuenta_empresa_id'  => 3,
                'metodo_pago'        => 'transferencia',
                'codigo_seguimiento' => 'TRF-INT-2026-0002',
                'observaciones'      => 'Pago flete parcial - transbordado en frontera',
                'created_by'         => 1,
                'updated_by'         => 1,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],

            // ─── Contrato 3 - Camión 3 (contrato_camion_id=6, monto_acordado=12000 BOB) ───
            // Adelanto en efectivo
            [
                'uuid'               => Str::uuid(),
                'lote_pago_id'       => null,
                'contrato_camion_id' => 6,
                'tipo_pago'          => 'adelanto',
                'monto'              => 3500.00,
                'moneda_pago'        => 'BOB',
                'tipo_cambio'        => 1.0000,
                'fecha_pago'         => '2026-03-18',
                'receptor_type'      => 'App\Models\OperadorTransporte',
                'receptor_id'        => 3,
                'cuenta_origen_id'   => null,
                'cuenta_destino_id'  => 4,
                'cuenta_empresa_id'  => 1, // Caja Principal BOB
                'metodo_pago'        => 'efectivo',
                'codigo_seguimiento' => null,
                'observaciones'      => 'Adelanto en efectivo - CHOQUE, Cochabamba a Trinidad',
                'created_by'         => 1,
                'updated_by'         => 1,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
        ]);
    }
}
