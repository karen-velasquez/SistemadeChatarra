<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Empresa extends Model
{
    use SoftDeletes;

    protected $table = 'empresas';

    protected $fillable = [
        'uuid', 'nombre', 'nit', 'razon_social', 'direccion',
        'telefono', 'email', 'logo', 'activo', 'created_by', 'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }

    public function cuentas()
    {
        return $this->hasMany(CuentaEmpresa::class, 'empresa_id');
    }

    public function cuentasActivas()
    {
        return $this->hasMany(CuentaEmpresa::class, 'empresa_id')->where('activo', true);
    }

    public function getSaldoTotalAttribute(): float
    {
        return $this->cuentas->sum('saldo_actual');
    }

    public function puedeEliminar(): bool
    {
        // Verificar si tiene cuentas con movimientos
        $tieneCuentasConMovimientos = $this->cuentas()
            ->whereHas('movimientos')
            ->exists();

        return !$tieneCuentasConMovimientos;
    }
}
