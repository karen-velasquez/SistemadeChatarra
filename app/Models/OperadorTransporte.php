<?php

namespace App\Models;

use Illuminate\Support\Str;
use Wildside\Userstamps\Userstamps;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OperadorTransporte extends Model implements Auditable
{
    use HasFactory, Userstamps, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'operadores_transporte';

    protected $fillable = [
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'ci',
        'ci_pais_id',
        'telefono',
        'email',
        'direccion',
        'tipo_operador',
        'licencia_numero',
        'licencia_pais_id',
        'licencia_vencimiento',
        'estado',
        'doc_carnet',
        'doc_licencia',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'licencia_vencimiento' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid()->toString();
        });
    }

    public function ciPais()
    {
        return $this->belongsTo(Parametro::class, 'ci_pais_id');
    }

    public function licenciaPais()
    {
        return $this->belongsTo(Parametro::class, 'licencia_pais_id');
    }

    // Camiones de los que es propietario
    public function camionesComoProietario()
    {
        return $this->hasMany(Camion::class, 'propietario_id');
    }

    // Asignaciones como conductor (historial completo)
    public function asignacionesConductor()
    {
        return $this->hasMany(CamionConductor::class, 'conductor_id');
    }

    // Solo asignaciones activas como conductor
    public function asignacionesActivasConductor()
    {
        return $this->hasMany(CamionConductor::class, 'conductor_id')->whereNull('fecha_fin');
    }

    // ¿Puede ser conductor?
    public function puedeConducir(): bool
    {
        return in_array($this->tipo_operador, ['chofer', 'ambos']) && !empty($this->licencia_numero);
    }

    // Nombre completo
    public function getNombreCompletoAttribute(): string
    {
        $apellidos = trim($this->apellido_paterno . ' ' . ($this->apellido_materno ?? ''));
        return $this->nombre . ' ' . $apellidos;
    }
}
