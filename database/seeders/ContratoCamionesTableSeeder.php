<?php

namespace Database\Seeders;

use App\Models\ContratoCamion;
use Illuminate\Database\Seeder;

class ContratoCamionesTableSeeder extends Seeder
{
    public function run(): void
    {
        // Contrato 1 (CTR-2026-001) — 3 camiones
        ContratoCamion::create([
            'contrato_id'     => 1,
            'camion_id'       => 1,   // ABC-1234 (VOLVO)
            'conductor_id'    => 2,   // PEDRO FLORES (chofer)
            'toneladas'       => 18.500,
            'monto_acordado'  => 15000.00,
            'moneda_flete'    => 'BOB',
            'fecha_asignacion'=> '2026-01-05',
            'estado_entrega'  => 'Entregado',
            'activo'          => true,
            'created_by'      => 1,
            'updated_by'      => 1,
        ]);

        ContratoCamion::create([
            'contrato_id'     => 1,
            'camion_id'       => 2,   // XYZ-5678 (MERCEDES)
            'conductor_id'    => 2,   // PEDRO FLORES
            'toneladas'       => 22.000,
            'monto_acordado'  => 18000.00,
            'moneda_flete'    => 'BOB',
            'fecha_asignacion'=> '2026-01-10',
            'estado_entrega'  => 'Pendiente',
            'activo'          => true,
            'created_by'      => 1,
            'updated_by'      => 1,
        ]);

        ContratoCamion::create([
            'contrato_id'     => 1,
            'camion_id'       => 3,   // QRS-9012 (SCANIA)
            'conductor_id'    => 3,   // MARIA CHOQUE
            'toneladas'       => 17.200,
            'monto_acordado'  => 14000.00,
            'moneda_flete'    => 'BOB',
            'fecha_asignacion'=> '2026-01-15',
            'estado_entrega'  => 'Pendiente',
            'activo'          => true,
            'created_by'      => 1,
            'updated_by'      => 1,
        ]);

        // Contrato 2 (CTR-2026-002) — Internacional
        ContratoCamion::create([
            'contrato_id'     => 2,
            'camion_id'       => 1,   // ABC-1234
            'conductor_id'    => 2,
            'toneladas'       => 20.000,
            'monto_acordado'  => 2800.00,
            'moneda_flete'    => 'USD',
            'fecha_asignacion'=> '2026-02-05',
            'estado_entrega'  => 'Pendiente',
            'activo'          => true,
            'created_by'      => 1,
            'updated_by'      => 1,
        ]);

        ContratoCamion::create([
            'contrato_id'     => 2,
            'camion_id'       => 2,
            'conductor_id'    => 3,
            'toneladas'       => 24.500,
            'monto_acordado'  => 3200.00,
            'moneda_flete'    => 'USD',
            'fecha_asignacion'=> '2026-02-10',
            'estado_entrega'  => 'Pendiente',
            'activo'          => true,
            'created_by'      => 1,
            'updated_by'      => 1,
        ]);

        // Contrato 3 (CTR-2026-003)
        ContratoCamion::create([
            'contrato_id'     => 3,
            'camion_id'       => 3,
            'conductor_id'    => 3,
            'toneladas'       => 16.800,
            'monto_acordado'  => 12000.00,
            'moneda_flete'    => 'BOB',
            'fecha_asignacion'=> '2026-03-18',
            'estado_entrega'  => 'Pendiente',
            'activo'          => true,
            'created_by'      => 1,
            'updated_by'      => 1,
        ]);
    }
}
