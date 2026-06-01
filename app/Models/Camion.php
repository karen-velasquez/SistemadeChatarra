<?php

namespace App\Models;

use Illuminate\Support\Str;
use Wildside\Userstamps\Userstamps;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Camion extends Model implements Auditable
{
    use HasFactory, Userstamps, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'camiones';

    protected $fillable = [
        'placa',
        'placa_pais_id',
        'tipo_vehiculo_id',
        'marca_id',
        'modelo',
        'anio',
        'capacidad_kg',
        'color',
        'estado',
        'documento_ruat',
        'propietario_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid()->toString();
        });
    }

    // Propietario del camión
    public function propietario()
    {
        return $this->belongsTo(OperadorTransporte::class, 'propietario_id');
    }

    public function marca()
    {
        return $this->belongsTo(Parametro::class, 'marca_id');
    }

    public function tipoVehiculo()
    {
        return $this->belongsTo(Parametro::class, 'tipo_vehiculo_id');
    }

    public function placaPais()
    {
        return $this->belongsTo(Parametro::class, 'placa_pais_id');
    }

    // Historial completo de conductores
    public function conductores()
    {
        return $this->hasMany(CamionConductor::class, 'camion_id');
    }

    // Conductor activo actual (fecha_fin NULL)
    public function conductorActual()
    {
        return $this->hasOne(CamionConductor::class, 'camion_id')->whereNull('fecha_fin')->latest('fecha_inicio');
    }

    public function fotos()
    {
        return $this->hasMany(CamionFoto::class, 'camion_id');
    }
}
