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
        'uuid', 'nombre', 'nit', 'precio_referencia', 'razon_social', 'direccion',
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

    // Precio sugerido por empresa facturadora: se autoactualiza cuando se
    // registra/edita una entrega con un precio distinto al guardado, para
    // que la próxima sugerencia ya refleje el precio más reciente facturado.
    public static function actualizarPrecioReferencia(?int $empresaId, ?float $precio): void
    {
        if (!$empresaId || !$precio) return;
        static::where('id', $empresaId)
            ->where(function ($q) use ($precio) {
                $q->whereNull('precio_referencia')->orWhere('precio_referencia', '!=', $precio);
            })
            ->update(['precio_referencia' => $precio]);
    }
}
