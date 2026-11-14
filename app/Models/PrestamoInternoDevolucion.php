<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PrestamoInternoDevolucion extends Model
{
    use SoftDeletes;

    protected $table = 'prestamo_interno_devoluciones';

    protected $fillable = [
        'uuid', 'prestamo_interno_id', 'monto', 'fecha', 'concepto', 'metodo_pago',
        'codigo_seguimiento', 'voucher', 'estado', 'created_by',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'float',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }

    public function prestamo()
    {
        return $this->belongsTo(PrestamoInterno::class, 'prestamo_interno_id');
    }
}
