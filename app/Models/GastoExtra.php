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
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid()->toString();
        });
    }
}