<?php

namespace App\Models;

use App\Models\CuentaEmpresa;
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

        // Al editar un movimiento el saldo debe seguir el cambio: se revierte el
        // importe anterior y se aplica el nuevo (pueden variar monto, TC o cuenta).
        static::updating(function ($model) {
            if ($model->isDirty(['monto', 'tipo_cambio'])) {
                $model->monto_bolivianos = $model->monto * ($model->tipo_cambio ?? 1);
            }
        });

        static::updated(function ($model) {
            $montoAnterior  = $model->getOriginal('monto_bolivianos');
            $tipoAnterior   = $model->getOriginal('tipo');
            $cuentaAnterior = $model->getOriginal('cuenta_empresa_id');

            if ($montoAnterior == $model->monto_bolivianos
                && $tipoAnterior === $model->tipo
                && $cuentaAnterior == $model->cuenta_empresa_id) {
                return;
            }

            // Revertir el efecto anterior sobre la cuenta que lo recibió
            if ($cuentaAnterior && ($cuenta = CuentaEmpresa::find($cuentaAnterior))) {
                $tipoAnterior === 'ingreso'
                    ? $cuenta->decrement('saldo_actual', $montoAnterior)
                    : $cuenta->increment('saldo_actual', $montoAnterior);
            }

            // Aplicar el efecto nuevo
            if ($cuenta = $model->cuentaEmpresa()->first()) {
                $model->tipo === 'ingreso'
                    ? $cuenta->increment('saldo_actual', $model->monto_bolivianos)
                    : $cuenta->decrement('saldo_actual', $model->monto_bolivianos);
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

    // Movimiento de tesorería espejo de un pago (PagoCliente, PagoProveedor, PagoCamion, PagoExtraLote):
    // monto, moneda, tipo de cambio, fecha, código y lote salen del propio pago.
    // monto_bolivianos lo calcula el hook creating().
    public static function registrarDePago(Model $pago, string $tipo, string $categoria, $cuentaEmpresaId, string $concepto, ?string $observaciones = null): self
    {
        return self::create([
            'lote_pago_id'       => $pago->lote_pago_id ?? null,
            'cuenta_empresa_id'  => $cuentaEmpresaId,
            'tipo'               => $tipo,
            'categoria'          => $categoria,
            'monto'              => $pago->monto,
            'moneda'             => $pago->moneda_pago ?? $pago->moneda,
            'tipo_cambio'        => $pago->tipo_cambio,
            'fecha'              => $pago->fecha_pago ?? $pago->fecha,
            'concepto'           => $concepto,
            'codigo_seguimiento' => $pago->codigo_seguimiento,
            'observaciones'      => $observaciones,
            'origen_type'        => get_class($pago),
            'origen_id'          => $pago->id,
            'created_by'         => auth()->id(),
            'updated_by'         => auth()->id(),
        ]);
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
