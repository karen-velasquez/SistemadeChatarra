<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReglaComision extends Model
{
    use SoftDeletes;

    protected $table = 'reglas_comision';

    protected $fillable = [
        'cliente_id',
        'empresa_facturadora_id',
        'monto_por_tonelada',
        'vigente_desde',
        'activo',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'monto_por_tonelada' => 'decimal:4',
        'vigente_desde'      => 'date',
        'activo'             => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($m) => $m->uuid = Str::uuid()->toString());
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function empresaFacturadora()
    {
        return $this->belongsTo(Empresa::class, 'empresa_facturadora_id');
    }

    /**
     * Busca la comisión especial por tonelada para un cliente + empresa
     * facturadora dados, vigente en la fecha de la venta. Prioriza la regla
     * más específica (cliente+empresa exactos, antes que reglas genéricas
     * con alguno de los dos en null) y, entre las que coincidan, la vigencia
     * más reciente que ya haya empezado (o sin vigente_desde, que rige siempre).
     * Devuelve null si no hay ninguna regla activa que aplique.
     */
    public static function montoParaVenta(?int $clienteId, ?int $empresaId, $fecha = null): ?float
    {
        if (!$clienteId && !$empresaId) return null;

        $fecha = $fecha ? \Illuminate\Support\Carbon::parse($fecha) : now();

        $regla = static::where('activo', true)
            ->where(function ($q) use ($clienteId) {
                $q->where('cliente_id', $clienteId)->orWhereNull('cliente_id');
            })
            ->where(function ($q) use ($empresaId) {
                $q->where('empresa_facturadora_id', $empresaId)->orWhereNull('empresa_facturadora_id');
            })
            ->where(function ($q) use ($fecha) {
                $q->whereNull('vigente_desde')->orWhere('vigente_desde', '<=', $fecha->toDateString());
            })
            ->orderByRaw('(cliente_id IS NOT NULL) + (empresa_facturadora_id IS NOT NULL) DESC')
            ->orderByRaw('vigente_desde IS NULL')
            ->orderByDesc('vigente_desde')
            ->first();

        return $regla ? (float) $regla->monto_por_tonelada : null;
    }
}
