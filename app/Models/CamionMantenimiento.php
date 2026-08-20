<?php

namespace App\Models;

use Illuminate\Support\Str;
use Wildside\Userstamps\Userstamps;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CamionMantenimiento extends Model implements Auditable
{
    use HasFactory, Userstamps, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'camion_mantenimientos';

    protected $fillable = [
        'camion_id',
        'taller_id',
        'cuenta_empresa_id',
        'categoria',
        'fecha',
        'tipo',
        'descripcion',
        'costo',
        'kilometraje',
        'comprobante',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Str::uuid()->toString();
        });

        // El mantenimiento descuenta el costo de la cuenta de empresa elegida.
        static::created(function ($model) {
            if ($model->cuenta_empresa_id) {
                Movimiento::registrarDeMantenimiento($model);
            }
        });

        // Al eliminar (soft delete) el mantenimiento se elimina su movimiento,
        // lo que revierte el saldo automáticamente (hook deleted() de Movimiento).
        static::deleted(function ($model) {
            $model->movimiento()->delete();
        });
    }

    public function camion()
    {
        return $this->belongsTo(Camion::class, 'camion_id');
    }

    public function taller()
    {
        return $this->belongsTo(Taller::class, 'taller_id');
    }

    public function cuentaEmpresa()
    {
        return $this->belongsTo(CuentaEmpresa::class, 'cuenta_empresa_id');
    }

    public function movimiento()
    {
        return $this->morphOne(Movimiento::class, 'origen', 'origen_type', 'origen_id');
    }
}
