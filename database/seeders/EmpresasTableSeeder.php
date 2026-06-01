<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class EmpresasTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Empresa principal
        DB::table('empresas')->insert([
            'uuid'        => Str::uuid(),
            'nombre'      => 'Chatarra Bolivia S.R.L.',
            'nit'         => '1234567890',
            'razon_social'=> 'CHATARRA BOLIVIA SOCIEDAD DE RESPONSABILIDAD LIMITADA',
            'direccion'   => 'Av. Blanco Galindo Km 5, Cochabamba',
            'telefono'    => '4-4521234',
            'email'       => 'info@chatarrabolivia.com',
            'activo'      => true,
            'created_by'  => 1,
            'updated_by'  => 1,
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);

        // Cuentas de la empresa
        DB::table('cuentas_empresa')->insert([
            [
                'uuid'          => Str::uuid(),
                'empresa_id'    => 1,
                'nombre_cuenta' => 'Caja Principal BOB',
                'banco'         => 'Efectivo',
                'numero_cuenta' => null,
                'moneda'        => 'BOB',
                'saldo_inicial' => 50000.00,
                'saldo_actual'  => 38500.00,
                'activo'        => true,
                'descripcion'   => 'Caja en efectivo bolivianos',
                'created_by'    => 1,
                'updated_by'    => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'uuid'          => Str::uuid(),
                'empresa_id'    => 1,
                'nombre_cuenta' => 'Banco Unión BOB',
                'banco'         => 'Banco Unión',
                'numero_cuenta' => '1-6123456-0-1',
                'moneda'        => 'BOB',
                'saldo_inicial' => 120000.00,
                'saldo_actual'  => 95750.00,
                'activo'        => true,
                'descripcion'   => 'Cuenta corriente BOB en Banco Unión',
                'created_by'    => 1,
                'updated_by'    => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'uuid'          => Str::uuid(),
                'empresa_id'    => 1,
                'nombre_cuenta' => 'Banco Bisa USD',
                'banco'         => 'Banco Bisa',
                'numero_cuenta' => '0-9876543-2-1',
                'moneda'        => 'USD',
                'saldo_inicial' => 15000.00,
                'saldo_actual'  => 11200.00,
                'activo'        => true,
                'descripcion'   => 'Cuenta corriente USD en Banco Bisa',
                'created_by'    => 1,
                'updated_by'    => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
        ]);
    }
}
