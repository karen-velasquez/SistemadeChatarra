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
        'activo',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'monto_por_tonelada' => 'decimal:4',
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
     * facturadora dados. Prioriza la regla más específica: cliente+empresa
     * exactos, antes que reglas genéricas con alguno de los dos en null.
     * Devuelve null si no hay ninguna regla activa que aplique.
     */
    public static function montoParaVenta(?int $clienteId, ?int $empresaId): ?float
    {
        if (!$clienteId && !$empresaId) return null;

        $regla = static::where('activo', true)
            ->where(function ($q) use ($clienteId) {
                $q->where('cliente_id', $clienteId)->orWhereNull('cliente_id');
            })
            ->where(function ($q) use ($empresaId) {
                $q->where('empresa_facturadora_id', $empresaId)->orWhereNull('empresa_facturadora_id');
            })
            ->orderByRaw('(cliente_id IS NOT NULL) + (empresa_facturadora_id IS NOT NULL) DESC')
            ->first();

        return $regla ? (float) $regla->monto_por_tonelada : null;
    }
}
