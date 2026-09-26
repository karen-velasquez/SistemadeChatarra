<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReglaIt extends Model
{
    use SoftDeletes;

    protected $table = 'reglas_it';

    protected $fillable = [
        'cliente_id',
        'empresa_facturadora_id',
        'monto_por_tonelada',
        'fecha_inicio',
        'fecha_fin',
        'activo',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'monto_por_tonelada' => 'decimal:4',
        'fecha_inicio'       => 'date',
        'fecha_fin'          => 'date',
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
     * Busca el IT por tonelada para un cliente + empresa facturadora dados,
     * vigente en la fecha de la venta (dentro del rango fecha_inicio..fecha_fin;
     * cualquiera de los dos puede quedar abierto). Prioriza la regla más
     * específica (cliente+empresa exactos, antes que reglas genéricas con
     * alguno de los dos en null) y, entre las que coincidan, la vigencia que
     * empezó más recientemente.
     * Devuelve 0.0 si no hay ninguna regla vigente que aplique: sin regla,
     * el IT de esa venta es 0, no un porcentaje por defecto.
     */
    public static function montoParaVenta(?int $clienteId, ?int $empresaId, $fecha = null): float
    {
        if (!$clienteId && !$empresaId) return 0.0;

        $fecha = $fecha ? \Illuminate\Support\Carbon::parse($fecha) : now();

        $regla = static::where('activo', true)
            ->where(function ($q) use ($clienteId) {
                $q->where('cliente_id', $clienteId)->orWhereNull('cliente_id');
            })
            ->where(function ($q) use ($empresaId) {
                $q->where('empresa_facturadora_id', $empresaId)->orWhereNull('empresa_facturadora_id');
            })
            ->where(function ($q) use ($fecha) {
                $q->whereNull('fecha_inicio')->orWhere('fecha_inicio', '<=', $fecha->toDateString());
            })
            ->where(function ($q) use ($fecha) {
                $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $fecha->toDateString());
            })
            ->orderByRaw('(cliente_id IS NOT NULL) + (empresa_facturadora_id IS NOT NULL) DESC')
            ->orderByRaw('fecha_inicio IS NULL')
            ->orderByDesc('fecha_inicio')
            ->first();

        return $regla ? (float) $regla->monto_por_tonelada : 0.0;
    }
}
