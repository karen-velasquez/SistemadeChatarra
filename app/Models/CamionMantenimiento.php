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
    }

    public function camion()
    {
        return $this->belongsTo(Camion::class, 'camion_id');
    }

    public function taller()
    {
        return $this->belongsTo(Taller::class, 'taller_id');
    }
}
