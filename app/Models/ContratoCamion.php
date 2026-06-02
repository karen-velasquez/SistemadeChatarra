<?php

namespace App\Models;

use Illuminate\Support\Str;
use Wildside\Userstamps\Userstamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContratoCamion extends Model
{
    use HasFactory, Userstamps;

    protected $table = 'contrato_camiones';

    protected $fillable = [
        'contrato_id',
        'camion_id',
        'toneladas',
        'monto_acordado',
        'moneda_flete',
        'fecha_asignacion',
        'estado_entrega',
        'conductor_id',
        'observaciones',
        'activo',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'toneladas'        => 'decimal:3',
        'monto_acordado'   => 'decimal:2',
        'fecha_asignacion' => 'date',
        'activo'           => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid()->toString();
        });
    }

    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }

    public function camion()
    {
        return $this->belongsTo(Camion::class, 'camion_id');
    }

    public function conductor()
    {
        return $this->belongsTo(OperadorTransporte::class, 'conductor_id');
    }

    public function tramos()
    {
        return $this->hasMany(Tramo::class, 'contrato_camion_id');
    }

    // Tramos raíz (sin padre): el primer tramo de cada cadena
    public function tramosRaiz()
    {
        return $this->hasMany(Tramo::class, 'contrato_camion_id')->whereNull('tramo_padre_id');
    }

    public function pagos()
    {
        return $this->hasMany(PagoCamion::class, 'contrato_camion_id');
    }

    // Peso total entregado al cliente (tramos finales Entregado)
    public function getPesoEntregadoAttribute(): float
    {
        return (float) $this->tramos()
            ->whereDoesntHave('tramosHijos')
            ->where('estado', 'Entregado')
            ->sum('peso_llegada');
    }

    // Estado de entrega calculado dinámicamente basado en los tramos hoja del árbol.
    // Recorre el árbol de tramos descendientes (los hijos pueden vivir en otros
    // ContratoCamion por transbordo) y revisa el estado de las hojas reales.
    public function getEstadoEntregaCalculadoAttribute(): string
    {
        // Tramos raíz de este ContratoCamion (sin padre dentro de la cadena propia)
        $tramosRaiz = $this->tramos->whereNull('tramo_padre_id');

        // Si no hay tramos raíz, usar el valor de la base de datos
        if ($tramosRaiz->isEmpty()) {
            return $this->attributes['estado_entrega'] ?? 'Pendiente';
        }

        // Recolectar todas las hojas reales del árbol descendiente
        $hojas = collect();
        foreach ($tramosRaiz as $raiz) {
            $this->recolectarHojas($raiz, $hojas);
        }

        // Si no se encontraron hojas, usar valor de BD
        if ($hojas->isEmpty()) {
            return $this->attributes['estado_entrega'] ?? 'Pendiente';
        }

        // Todas las hojas deben estar entregadas o desactivadas
        $todosEntregados = $hojas->every(function($tramo) {
            return in_array($tramo->estado, ['Entregado', 'Desactivado']);
        });

        return $todosEntregados ? 'Entregado' : 'Pendiente';
    }

    // Recorre recursivamente un tramo y acumula sus hojas (tramos sin hijos)
    private function recolectarHojas(Tramo $tramo, $hojas): void
    {
        $hijos = $tramo->tramosHijos;
        if ($hijos->isEmpty()) {
            $hojas->push($tramo);
            return;
        }
        foreach ($hijos as $hijo) {
            $this->recolectarHojas($hijo, $hojas);
        }
    }

    // Descuento en monto: se toma el mayor descuento_porcentaje registrado en tramos finales
    public function getDescuentoMontoAttribute(): float
    {
        if (!$this->monto_acordado) return 0;
        $pct = (float) $this->tramos()
            ->whereDoesntHave('tramosHijos')
            ->whereNotNull('descuento_porcentaje')
            ->max('descuento_porcentaje');
        return round($this->monto_acordado * $pct / 100, 2);
    }

    // Monto neto a pagar después del descuento
    public function getMontoNetoAttribute(): float
    {
        return max(0, (float)($this->monto_acordado ?? 0) - $this->descuento_monto);
    }

    // Total ya pagado en la moneda del flete
    // Si el pago se hizo en la misma moneda: suma directo
    // Si se hizo en otra moneda (ej: pagó en BOB un flete en BRL): convierte dividiendo por tipo_cambio
    public function getTotalPagadoAttribute(): float
    {
        $monedaFlete = $this->moneda_flete ?? 'BOB';
        return (float) $this->pagos()
            ->whereNull('deleted_at')
            ->get()
            ->sum(function ($p) use ($monedaFlete) {
                $monto = (float) $p->monto;
                $tc    = (float) $p->tipo_cambio ?: 1;
                if ($p->moneda_pago === $monedaFlete) {
                    return $monto;
                }
                // pagó en moneda distinta: convertir a BOB y luego a moneda flete
                // monto_en_bob = monto * tc_pago; en_moneda_flete = monto_en_bob / tc_flete
                // Pero no tenemos tc_flete guardado → simplificamos:
                // si moneda_flete es BOB, dividir entre tc_pago no tiene sentido,
                // así que el caso normal es: pago en moneda_flete → suma directo
                // pago en BOB con flete en extranjera → monto/tc (tc = cuánto vale 1 extranjera en BOB)
                if ($monedaFlete !== 'BOB' && $p->moneda_pago === 'BOB') {
                    return $tc > 0 ? $monto / $tc : 0;
                }
                // fallback: tratar como misma moneda
                return $monto;
            });
    }

    // Saldo pendiente en la moneda del flete
    public function getSaldoPendienteAttribute(): float
    {
        return max(0, $this->monto_neto - $this->total_pagado);
    }
}
