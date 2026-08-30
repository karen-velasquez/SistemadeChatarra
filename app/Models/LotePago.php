<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LotePago extends Model
{
    use SoftDeletes;

    protected $table = 'lotes_pago';

    protected $fillable = [
        'tipo',
        'codigo_provisional',
        'codigo_real',
        'fecha_pago',
        'metodo_pago',
        'cuenta_origen_id',
        'observaciones',
        'created_by',
    ];

    protected $casts = [
        'fecha_pago' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($m) => $m->uuid = Str::uuid()->toString());
    }

    public function pagosProveedor()
    {
        return $this->hasMany(PagoProveedor::class, 'lote_pago_id');
    }

    public function pagosCamion()
    {
        return $this->hasMany(PagoCamion::class, 'lote_pago_id');
    }

    public function pagosCliente()
    {
        return $this->hasMany(PagoCliente::class, 'lote_pago_id');
    }

    public function movimientos()
    {
        return $this->hasMany(Movimiento::class, 'lote_pago_id');
    }

    public function cuentaOrigen()
    {
        return $this->belongsTo(CuentaEmpresa::class, 'cuenta_origen_id');
    }

    public function getCodigoVigenteAttribute(): ?string
    {
        return $this->codigo_real ?? $this->codigo_provisional;
    }

    public function getTotalPagosAttribute(): int
    {
        return match ($this->tipo) {
            'proveedor' => $this->pagosProveedor()->count(),
            'cliente'   => $this->pagosCliente()->count(),
            default     => $this->pagosCamion()->count(),
        };
    }

    private function pagosRelacion()
    {
        return match ($this->tipo) {
            'proveedor' => $this->pagosProveedor(),
            'cliente'   => $this->pagosCliente(),
            default     => $this->pagosCamion(),
        };
    }

    // El banco da un código real por cada pago individual, no uno solo para
    // todo el lote: el estado refleja cuántos de esos pagos ya lo tienen.
    public function getConCodigoRealAttribute(): int
    {
        return $this->pagosRelacion()->whereNotNull('codigo_seguimiento')->where('codigo_seguimiento', '!=', '')->count();
    }

    public function getEstadoCodigoAttribute(): string
    {
        $total = $this->total_pagos;
        if ($total === 0) return 'pendiente';
        $conCodigo = $this->con_codigo_real;
        if ($conCodigo === 0) return 'pendiente';
        return $conCodigo === $total ? 'confirmado' : 'parcial';
    }
}
