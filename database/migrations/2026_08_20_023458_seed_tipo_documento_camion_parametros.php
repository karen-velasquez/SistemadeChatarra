<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $valores = [
            'RUAT'     => 'RUAT',
            'CONTRATO' => 'Contrato',
            'IMPUESTO' => 'Pago de impuesto anual',
            'SEGURO'   => 'Seguro del camión',
            'SOAT'     => 'SOAT (Póliza)',
            'OTRO'     => 'Otro',
        ];

        foreach ($valores as $valor => $descripcion) {
            DB::table('parametros')->insert([
                'uuid'        => Str::uuid()->toString(),
                'tipo'        => 'tipo_documento_camion',
                'valor'       => $valor,
                'descripcion' => $descripcion,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('parametros')->where('tipo', 'tipo_documento_camion')->delete();
    }
};
