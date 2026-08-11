<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CuentaEmpresa extends Model
{
    use SoftDeletes;

    protected $table = 'cuentas_empresa';

    protected $fillable = [
        'uuid', 'empresa_id', 'nombre_cuenta', 'banco_id', 'numero_cuenta',
        'moneda', 'saldo_inicial', 'saldo_actual', 'activo', 'descripcion',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'saldo_inicial' => 'float',
        'saldo_actual'  => 'float',
        'activo'        => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
            if (is_null($model->saldo_actual)) {
                $model->saldo_actual = $model->saldo_inicial ?? 0;
            }
        });
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function banco()
    {
        return $this->belongsTo(Banco::class, 'banco_id');
    }

    public function movimientos()
    {
        return $this->hasMany(Movimiento::class, 'cuenta_empresa_id');
    }

    public function puedeEliminar(): bool
    {
        return !$this->movimientos()->exists();
    }

    public function prestamosOtorgados()
    {
        return $this->hasMany(PrestamoInterno::class, 'cuenta_origen_id');
    }

    public function prestamosRecibidos()
    {
        return $this->hasMany(PrestamoInterno::class, 'cuenta_destino_id');
    }
}
