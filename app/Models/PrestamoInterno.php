<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PrestamoInterno extends Model
{
    use SoftDeletes;

    protected $table = 'prestamos_internos';

    protected $fillable = [
        'uuid', 'cuenta_origen_id', 'cuenta_destino_id', 'monto_original',
        'monto_devuelto', 'moneda', 'fecha_prestamo', 'fecha_vencimiento',
        'estado', 'concepto', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'fecha_prestamo'   => 'date',
        'fecha_vencimiento'=> 'date',
        'monto_original'   => 'float',
        'monto_devuelto'   => 'float',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }

    public function cuentaOrigen()
    {
        return $this->belongsTo(CuentaEmpresa::class, 'cuenta_origen_id');
    }

    public function cuentaDestino()
    {
        return $this->belongsTo(CuentaEmpresa::class, 'cuenta_destino_id');
    }

    public function getMontoPendienteAttribute(): float
    {
        return max(0, $this->monto_original - $this->monto_devuelto);
    }

    public function getPorcentajeDevueltoAttribute(): float
    {
        if ($this->monto_original <= 0) return 0;
        return min(100, round(($this->monto_devuelto / $this->monto_original) * 100, 1));
    }
}
