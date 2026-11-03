<?php

namespace App\Http\Controllers\Concerns;

use App\Models\LotePago;
use App\Models\PagoCamion;
use App\Models\PagoCliente;
use App\Models\PagoProveedor;

trait GeneraCodigoSeguimientoUnico
{
    /**
     * Genera un código con el prefijo dado, verificando que no choque con
     * ningún código_seguimiento (pagos_camion/pagos_proveedor/pagos_cliente)
     * ni con ningún codigo_provisional/codigo_real de lotes_pago. Los cuatro
     * comparten el mismo "espacio de nombres" visual en Tesorería, así que
     * la unicidad se verifica cruzando las cuatro fuentes, no solo la propia.
     */
    protected function generarCodigoUnico(string $prefijo): string
    {
        do {
            $codigo = $prefijo . '-' . strtoupper(bin2hex(random_bytes(4)));
        } while ($this->codigoYaExiste($codigo));

        return $codigo;
    }

    protected function codigoYaExiste(string $codigo, ?int $exceptoPagoId = null, ?string $exceptoPagoClase = null, ?int $exceptoLoteId = null, array $exceptoPagoIds = []): bool
    {
        $qCamion    = PagoCamion::withTrashed()->where('codigo_seguimiento', $codigo);
        $qProveedor = PagoProveedor::withTrashed()->where('codigo_seguimiento', $codigo);
        $qCliente   = PagoCliente::withTrashed()->where('codigo_seguimiento', $codigo);

        // IDs a excluir de la propia clase: el pago que se está editando, más
        // (si se pasan) los demás pagos de su mismo lote — estos últimos porque
        // todos arrancan con el código provisional compartido del lote, que no
        // cuenta como "en uso" por otro pago hasta que cada uno tenga su propio
        // código real confirmado.
        $idsExcluir = array_unique(array_filter(array_merge([$exceptoPagoId], $exceptoPagoIds)));

        if (!empty($idsExcluir) && $exceptoPagoClase) {
            foreach ([PagoCamion::class => $qCamion, PagoProveedor::class => $qProveedor, PagoCliente::class => $qCliente] as $clase => $query) {
                if ($clase === $exceptoPagoClase) {
                    $query->whereNotIn('id', $idsExcluir);
                }
            }
        }

        $qLoteProvisional = LotePago::where('codigo_provisional', $codigo);
        $qLoteReal        = LotePago::where('codigo_real', $codigo);
        if ($exceptoLoteId) {
            $qLoteProvisional->where('id', '!=', $exceptoLoteId);
            $qLoteReal->where('id', '!=', $exceptoLoteId);
        }

        return $qCamion->exists()
            || $qProveedor->exists()
            || $qCliente->exists()
            || $qLoteProvisional->exists()
            || $qLoteReal->exists();
    }

    /**
     * Para validar un código escrito a mano por el usuario (ej. transferencia):
     * true si es válido (no vacío y no choca con otro registro).
     * $exceptoPagoId/$exceptoPagoClase permiten que un pago conserve su propio
     * código al editarse sin que se rechace contra sí mismo; $exceptoLoteId hace
     * lo mismo cuando lo que se edita es un LotePago (Tesorería → Lotes de Pago).
     */
    protected function codigoDisponible(?string $codigo, ?int $exceptoPagoId = null, ?string $exceptoPagoClase = null, ?int $exceptoLoteId = null, array $exceptoPagoIds = []): bool
    {
        if (!$codigo) {
            return true;
        }
        return !$this->codigoYaExiste($codigo, $exceptoPagoId, $exceptoPagoClase, $exceptoLoteId, $exceptoPagoIds);
    }
}
