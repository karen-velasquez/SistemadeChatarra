<?php

namespace App\Console\Commands;

use App\Models\LoteEntrega;
use App\Models\Proveedor;
use Illuminate\Console\Command;

class CrearLotesSemana extends Command
{
    protected $signature   = 'lotes:crear-semana {--forzar : Crear aunque no sea viernes}';
    protected $description = 'Crea los lotes de entrega de la semana actual para todos los proveedores activos (se ejecuta los viernes).';

    public function handle(): int
    {
        $hoy    = now();
        $esFin  = $this->option('forzar') || $hoy->isFriday();

        if (! $esFin) {
            $this->info('No es viernes. Usa --forzar para ejecutar de todas formas.');
            return Command::SUCCESS;
        }

        $proveedores = Proveedor::whereNull('deleted_at')->get();

        if ($proveedores->isEmpty()) {
            $this->warn('No hay proveedores activos.');
            return Command::SUCCESS;
        }

        $creados   = 0;
        $existentes = 0;

        foreach ($proveedores as $proveedor) {
            $lote = LoteEntrega::obtenerOCrearSemanaActual($proveedor->id);

            // wasRecentlyCreated es true si firstOrCreate acaba de insertarlo
            if ($lote->wasRecentlyCreated) {
                $creados++;
                $this->line("  <fg=green>✓</> {$proveedor->nombre} → {$lote->codigo}");
            } else {
                $existentes++;
                $this->line("  <fg=gray>–</> {$proveedor->nombre} → {$lote->codigo} (ya existía)");
            }
        }

        $this->info("Proceso completado: {$creados} creados, {$existentes} ya existían.");
        return Command::SUCCESS;
    }
}
