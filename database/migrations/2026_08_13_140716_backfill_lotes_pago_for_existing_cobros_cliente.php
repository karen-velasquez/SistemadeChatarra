<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Agrupa retroactivamente los cobros masivos a clientes que ya existían
     * antes de que PagoCliente tuviera lote_pago_id: cada codigo_seguimiento
     * compartido se convierte en un LotePago (tipo 'cliente'), igual que ya
     * se hace para pagos nuevos de proveedor/camión/cliente.
     */
    public function up(): void
    {
        $pagos = DB::table('pagos_cliente')
            ->whereNull('lote_pago_id')
            ->whereNotNull('codigo_seguimiento')
            ->orderBy('id')
            ->get();

        $grupos = $pagos->groupBy('codigo_seguimiento');

        foreach ($grupos as $codigo => $lineas) {
            $primera = $lineas->first();
            $esProvisional = $primera->metodo_pago === 'qr';

            $loteId = DB::table('lotes_pago')->insertGetId([
                'uuid'               => (string) Str::uuid(),
                'tipo'               => 'cliente',
                'codigo_provisional' => $esProvisional ? $codigo : null,
                'codigo_real'        => $esProvisional ? null : $codigo,
                'fecha_pago'         => $primera->fecha_pago,
                'metodo_pago'        => $primera->metodo_pago,
                'cuenta_origen_id'   => $primera->cuenta_origen_id,
                'created_by'         => $primera->created_by,
                'created_at'         => $primera->created_at,
                'updated_at'         => now(),
            ]);

            DB::table('pagos_cliente')
                ->whereIn('id', $lineas->pluck('id'))
                ->update(['lote_pago_id' => $loteId]);
        }
    }

    /**
     * No revierte: quitar lote_pago_id de estos pagos históricos perdería
     * información sin ganar nada (los lotes creados quedan huérfanos pero
     * inofensivos si se prefiere no borrarlos).
     */
    public function down(): void
    {
        $loteIds = DB::table('lotes_pago')->where('tipo', 'cliente')->pluck('id');

        DB::table('pagos_cliente')->whereIn('lote_pago_id', $loteIds)->update(['lote_pago_id' => null]);
        DB::table('lotes_pago')->whereIn('id', $loteIds)->delete();
    }
};
