<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Movimiento extends Model
{
    use SoftDeletes;

    protected $table = 'movimientos';

    protected $fillable = [
        'uuid', 'lote_pago_id', 'cuenta_empresa_id', 'tipo', 'categoria', 'monto',
        'moneda', 'tipo_cambio', 'monto_bolivianos', 'fecha', 'concepto',
        'codigo_seguimiento', 'observaciones', 'origen_type', 'origen_id', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'fecha'            => 'date',
        'monto'            => 'float',
        'monto_bolivianos' => 'float',
        'tipo_cambio'      => 'float',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
            if (!$model->monto_bolivianos) {
                $model->monto_bolivianos = $model->monto * ($model->tipo_cambio ?? 1);
            }
        });

        static::created(function ($model) {
            $cuenta = $model->cuentaEmpresa;
            if ($cuenta) {
                if ($model->tipo === 'ingreso') {
                    $cuenta->increment('saldo_actual', $model->monto_bolivianos);
                } else {
                    $cuenta->decrement('saldo_actual', $model->monto_bolivianos);
                }
            }
        });

        static::deleted(function ($model) {
            $cuenta = $model->cuentaEmpresa;
            if ($cuenta) {
                if ($model->tipo === 'ingreso') {
                    $cuenta->decrement('saldo_actual', $model->monto_bolivianos);
                } else {
                    $cuenta->increment('saldo_actual', $model->monto_bolivianos);
                }
            }
        });
    }

    public function cuentaEmpresa()
    {
        return $this->belongsTo(CuentaEmpresa::class, 'cuenta_empresa_id');
    }

    public function origen()
    {
        return $this->morphTo();
    }

    public function lotePago()
    {
        return $this->belongsTo(LotePago::class, 'lote_pago_id');
    }

    public static function categoriaLabel(string $cat): string
    {
        return match($cat) {
            'anticipo_cliente'   => 'Anticipo de Cliente',
            'pago_cliente'       => 'Pago de Cliente',
            'pago_proveedor'     => 'Pago a Proveedor',
            'pago_camion'        => 'Pago a Camión',
            'gasto_extra'        => 'Gasto Extra',
            'pago_sueldo'        => 'Pago de Sueldo',
            'prestamo_otorgado'  => 'Préstamo Otorgado',
            'prestamo_recibido'  => 'Préstamo Recibido',
            'devolucion_prestamo'=> 'Devolución Préstamo',
            'otro'               => 'Otro',
            default              => $cat,
        };
    }
}
