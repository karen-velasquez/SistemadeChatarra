<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CuentasBancariasTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        // banco_id=1 = Banco Nacional de Bolivia (id 1 del seeder)
        // banco_id=6 = Banco Unión
        // banco_id=5 = Banco Bisa

        DB::table('cuentas_bancarias')->insert([
            // Cuenta del proveedor 1
            [
                'uuid'                 => Str::uuid(),
                'banco_id'             => 6,  // Banco Unión
                'tipo_titular'         => 'proveedor',
                'titular_id'           => 1,
                'titular_type'         => 'App\Models\Proveedor',
                'numero_cuenta'        => '1-5432100-3-1',
                'moneda'               => 'BOB',
                'alias'                => 'Cuenta principal proveedor',
                'nro_documento'        => '1234567890',
                'sucursal_departamento'=> 'Cochabamba',
                'activo'               => true,
                'created_by'           => 1,
                'updated_by'           => 1,
                'created_at'           => $now,
                'updated_at'           => $now,
            ],
            // Cuenta del proveedor 2
            [
                'uuid'                 => Str::uuid(),
                'banco_id'             => 5,  // Banco Bisa
                'tipo_titular'         => 'proveedor',
                'titular_id'           => 2,
                'titular_type'         => 'App\Models\Proveedor',
                'numero_cuenta'        => '0-8765432-1-0',
                'moneda'               => 'USD',
                'alias'                => 'Cuenta USD proveedor',
                'nro_documento'        => '9876543210',
                'sucursal_departamento'=> 'Santa Cruz',
                'activo'               => true,
                'created_by'           => 1,
                'updated_by'           => 1,
                'created_at'           => $now,
                'updated_at'           => $now,
            ],
            // Cuenta del operador 1 (JUAN CARLOS MAMANI)
            [
                'uuid'                 => Str::uuid(),
                'banco_id'             => 1,  // Banco Nacional
                'tipo_titular'         => 'operador',
                'titular_id'           => 1,
                'titular_type'         => 'App\Models\OperadorTransporte',
                'numero_cuenta'        => '100-1234567-8',
                'moneda'               => 'BOB',
                'alias'                => 'Cuenta flete MAMANI',
                'nro_documento'        => '4512367',
                'sucursal_departamento'=> 'La Paz',
                'activo'               => true,
                'created_by'           => 1,
                'updated_by'           => 1,
                'created_at'           => $now,
                'updated_at'           => $now,
            ],
            // Cuenta del operador 3 (MARIA CHOQUE)
            [
                'uuid'                 => Str::uuid(),
                'banco_id'             => 6,  // Banco Unión
                'tipo_titular'         => 'operador',
                'titular_id'           => 3,
                'titular_type'         => 'App\Models\OperadorTransporte',
                'numero_cuenta'        => '1-9876543-2-0',
                'moneda'               => 'BOB',
                'alias'                => 'Cuenta flete CHOQUE',
                'nro_documento'        => '6234789',
                'sucursal_departamento'=> 'Cochabamba',
                'activo'               => true,
                'created_by'           => 1,
                'updated_by'           => 1,
                'created_at'           => $now,
                'updated_at'           => $now,
            ],
            // Cuenta del empleado 1 (ANA GUTIERREZ)
            [
                'uuid'                 => Str::uuid(),
                'banco_id'             => 6,  // Banco Unión
                'tipo_titular'         => 'empleado',
                'titular_id'           => 1,
                'titular_type'         => 'App\Models\Empleado',
                'numero_cuenta'        => '1-1122334-4-5',
                'moneda'               => 'BOB',
                'alias'                => 'Cuenta sueldo ANA',
                'nro_documento'        => '5234789',
                'sucursal_departamento'=> 'Cochabamba',
                'activo'               => true,
                'created_by'           => 1,
                'updated_by'           => 1,
                'created_at'           => $now,
                'updated_at'           => $now,
            ],
        ]);
    }
}
