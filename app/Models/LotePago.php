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
}
