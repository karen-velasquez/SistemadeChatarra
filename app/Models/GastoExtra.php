<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class GastoExtra extends Model
{
    use SoftDeletes;
    protected $table = 'gastos_extras';
    protected $fillable = [
        'contrato_id',
        'cuenta_bancaria_id',
        'cuenta_empresa_id',
        'tipo_pago',
        'nombre_titular',
        'categoria',
        'concepto',
        'fecha',
        'monto',
        'moneda',
        'monto_bolivianos',
        'tipo_cambio',
        'estado',
        'tipo_cambio',
        'comprobante_pago',
        'codigo_seguimiento',
        'estado',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = ['fecha' => 'date','monto' => 'decimal:2','tipo_cambio' => 'decimal:2',];
    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }
    public function cuentaBancaria()
    {
        return $this->belongsTo(CuentaBancaria::class, 'cuenta_bancaria_id');
    }
    public function cuentaEmpresa()
    {
        return $this->belongsTo(CuentaEmpresa::class, 'cuenta_empresa_id');
    }
    public function movimiento()
    {
        return $this->morphOne(Movimiento::class, 'origen', 'origen_type', 'origen_id');
    }
    public function usuarioCreador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function usuarioActualizador()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid()->toString();
        });

        // Al quedar en PAGADO (ya sea al crear o al editar) se descuenta el saldo
        // de la cuenta elegida. Al revertir a PENDIENTE se devuelve el dinero.
        static::saved(function ($model) {
            $eraPagado = $model->getOriginal('estado') === 'PAGADO';
            $esPagado  = $model->estado === 'PAGADO';

            if (!$eraPagado && $esPagado) {
                Movimiento::registrarDeGastoExtra($model);
            } elseif ($eraPagado && !$esPagado) {
                // delete() sobre la instancia (no sobre el query builder de la
                // relación) para que dispare el evento deleted() de Movimiento,
                // que es quien repone el saldo de la cuenta.
                $model->movimiento?->delete();
            } elseif ($eraPagado && $esPagado) {
                // Sigue PAGADO pero se editó (p. ej. código de transferencia o
                // comprobante): sincroniza el movimiento espejo ya existente.
                $model->movimiento()->update([
                    'cuenta_empresa_id'  => $model->cuenta_empresa_id,
                    'monto'              => $model->monto,
                    'moneda'             => $model->moneda,
                    'tipo_cambio'        => $model->tipo_cambio ?? 1,
                    'fecha'              => $model->fecha,
                    'concepto'           => 'Gasto Extra ' . $model->categoria . ' - ' . $model->concepto,
                    'codigo_seguimiento' => $model->codigo_seguimiento,
                    'updated_by'         => auth()->id(),
                ]);
            }
        });

        static::deleted(function ($model) {
            // delete() sobre la instancia, no sobre el query builder de la
            // relación: así dispara el evento deleted() de Movimiento y repone
            // el saldo de la cuenta (un delete() de query builder es un UPDATE
            // masivo que no ejecuta eventos de modelo).
            $model->movimiento?->delete();
        });
    }
}