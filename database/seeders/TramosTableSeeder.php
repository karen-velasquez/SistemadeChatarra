<?php

namespace Database\Seeders;

use App\Models\Tramo;
use Illuminate\Database\Seeder;

class TramosTableSeeder extends Seeder
{
    public function run(): void
    {
        // Contrato 1 - Camión 1 (contrato_camion_id=1) — Entregado
        Tramo::create([
            'contrato_camion_id'   => 1,
            'camion_id'            => 1,
            'conductor_id'         => 2,
            'cliente_id'           => 1,
            'origen'               => 'Santa Cruz',
            'destino'              => 'La Paz',
            'tipo_tramo'           => 'Nacional',
            'peso_declarado'       => 18.500,
            'peso_salida'          => 18.350,
            'peso_llegada'         => 18.200,
            'precio_por_tonelada'  => 820.0000,
            'moneda_venta'         => 'BOB',
            'fecha_salida'         => '2026-01-06',
            'fecha_llegada'        => '2026-01-09',
            'estado'               => 'Entregado',
            'activo'               => true,
            'created_by'           => 1,
            'updated_by'           => 1,
        ]);

        // Contrato 1 - Camión 2 (contrato_camion_id=2) — En ruta
        Tramo::create([
            'contrato_camion_id'   => 2,
            'camion_id'            => 2,
            'conductor_id'         => 2,
            'cliente_id'           => 1,
            'origen'               => 'Oruro',
            'destino'              => 'Cochabamba',
            'tipo_tramo'           => 'Nacional',
            'peso_declarado'       => 22.000,
            'peso_salida'          => 21.800,
            'peso_llegada'         => null,
            'precio_por_tonelada'  => 750.0000,
            'moneda_venta'         => 'BOB',
            'fecha_salida'         => '2026-01-12',
            'fecha_llegada'        => null,
            'estado'               => 'En ruta',
            'activo'               => true,
            'created_by'           => 1,
            'updated_by'           => 1,
        ]);

        // Contrato 1 - Camión 3 (contrato_camion_id=3) — Transbordando
        Tramo::create([
            'contrato_camion_id'   => 3,
            'camion_id'            => 3,
            'conductor_id'         => 3,
            'cliente_id'           => 1,
            'origen'               => 'Potosí',
            'destino'              => 'Cochabamba',
            'tipo_tramo'           => 'Nacional',
            'peso_declarado'       => 17.200,
            'peso_salida'          => 17.000,
            'peso_llegada'         => null,
            'precio_por_tonelada'  => 800.0000,
            'moneda_venta'         => 'BOB',
            'fecha_salida'         => '2026-01-16',
            'fecha_llegada'        => null,
            'estado'               => 'Transbordando',
            'activo'               => true,
            'created_by'           => 1,
            'updated_by'           => 1,
        ]);

        // Contrato 2 - Internacional (contrato_camion_id=4)
        Tramo::create([
            'contrato_camion_id'   => 4,
            'camion_id'            => 1,
            'conductor_id'         => 2,
            'cliente_id'           => 2,
            'origen'               => 'Santa Cruz',
            'destino'              => 'São Paulo',
            'tipo_tramo'           => 'Internacional',
            'peso_declarado'       => 20.000,
            'peso_salida'          => 19.900,
            'peso_llegada'         => null,
            'precio_por_tonelada'  => 140.0000,
            'moneda_venta'         => 'USD',
            'fecha_salida'         => '2026-02-07',
            'fecha_llegada'        => null,
            'estado'               => 'En ruta',
            'activo'               => true,
            'created_by'           => 1,
            'updated_by'           => 1,
        ]);

        // Contrato 2 - Camión 2 (contrato_camion_id=5)
        Tramo::create([
            'contrato_camion_id'   => 5,
            'camion_id'            => 2,
            'conductor_id'         => 3,
            'cliente_id'           => 2,
            'origen'               => 'La Paz',
            'destino'              => 'Buenos Aires',
            'tipo_tramo'           => 'Internacional',
            'peso_declarado'       => 24.500,
            'peso_salida'          => 24.300,
            'peso_llegada'         => null,
            'precio_por_tonelada'  => 130.0000,
            'moneda_venta'         => 'USD',
            'fecha_salida'         => '2026-02-12',
            'fecha_llegada'        => null,
            'estado'               => 'Transbordado',
            'activo'               => true,
            'created_by'           => 1,
            'updated_by'           => 1,
        ]);

        // Contrato 3 (contrato_camion_id=6)
        Tramo::create([
            'contrato_camion_id'   => 6,
            'camion_id'            => 3,
            'conductor_id'         => 3,
            'cliente_id'           => 3,
            'origen'               => 'Cochabamba',
            'destino'              => 'Trinidad',
            'tipo_tramo'           => 'Nacional',
            'peso_declarado'       => 16.800,
            'peso_salida'          => 16.600,
            'peso_llegada'         => null,
            'precio_por_tonelada'  => 950.0000,
            'moneda_venta'         => 'BOB',
            'fecha_salida'         => '2026-03-20',
            'fecha_llegada'        => null,
            'estado'               => 'En ruta',
            'activo'               => true,
            'created_by'           => 1,
            'updated_by'           => 1,
        ]);
    }
}
