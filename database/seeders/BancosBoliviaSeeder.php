<?php

namespace Database\Seeders;

use App\Models\Parametro;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class BancosBoliviaSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener ID de Bolivia
        $boliviaId = Parametro::where('tipo', 'paises')->where('valor', 'BOLIVIA')->first()?->id;

        $bancos = [
            ['nombre' => 'Banco Ganadero',              'codigo_banco' => null,    'codigo_swift' => 'BGADBO22'],
            ['nombre' => 'Banco Nacional de Bolivia',   'codigo_banco' => '1001',  'codigo_swift' => 'BNBOBOLX'],
            ['nombre' => 'Banco Mercantil Santa Cruz',  'codigo_banco' => '1003',  'codigo_swift' => 'MERBBOLX'],
            ['nombre' => 'Banco de Crédito de Bolivia', 'codigo_banco' => '1005',  'codigo_swift' => 'BCPLBOLX'],
            ['nombre' => 'Banco Do Brasil',             'codigo_banco' => '1008',  'codigo_swift' => 'BRASBOLX'],
            ['nombre' => 'Banco Bisa',                  'codigo_banco' => '1009',  'codigo_swift' => 'BANIBOLX'],
            ['nombre' => 'Banco Unión',                 'codigo_banco' => '1014',  'codigo_swift' => 'BAUNBO22'],
            ['nombre' => 'Banco Económico',             'codigo_banco' => '1016',  'codigo_swift' => 'BOEOBO22'],
            ['nombre' => 'Banco Solidario',             'codigo_banco' => '1017',  'codigo_swift' => 'BSOLBOLP'],
            ['nombre' => 'Banco FIE',                   'codigo_banco' => '1033',  'codigo_swift' => 'BFIFBOLP'],
            ['nombre' => 'Banco Fortaleza',             'codigo_banco' => '1034',  'codigo_swift' => 'BFORBOLP'],
            ['nombre' => 'Banco Fassil',                'codigo_banco' => '1035',  'codigo_swift' => 'BSCFBO22'],
            ['nombre' => 'Banco Prodem',                'codigo_banco' => '1036',  'codigo_swift' => 'BPRMBOLP'],
            ['nombre' => 'Cooperativa Jesús Nazareno',  'codigo_banco' => '3001',  'codigo_swift' => null],
            ['nombre' => 'E-EFECTIVO S.A.',             'codigo_banco' => '53001', 'codigo_swift' => null],
            ['nombre' => 'Banco PYME Ecofuturo S.A.A.','codigo_banco' => '74002', 'codigo_swift' => 'PYEOBOLP'],
            ['nombre' => 'Banco PYME de la Comunidad',  'codigo_banco' => '74003', 'codigo_swift' => 'PYCOBO23'],
        ];

        $now = now();

        foreach ($bancos as $banco) {
            $query = DB::table('bancos')->whereNull('deleted_at');

            if ($banco['codigo_banco'] !== null) {
                $existe = $query->where('codigo_banco', $banco['codigo_banco'])->exists();
            } else {
                $existe = $query->where('nombre', $banco['nombre'])->exists();
            }

            if (!$existe) {
                DB::table('bancos')->insert([
                    'uuid'         => Str::uuid()->toString(),
                    'nombre'       => $banco['nombre'],
                    'pais_id'      => $boliviaId,
                    'codigo_swift' => $banco['codigo_swift'],
                    'codigo_banco' => $banco['codigo_banco'],
                    'activo'       => true,
                    'created_by'   => 1,
                    'updated_by'   => 1,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);
            }
        }
    }
}
