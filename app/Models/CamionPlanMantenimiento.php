<?php

namespace App\Models;

use Illuminate\Support\Str;
use Wildside\Userstamps\Userstamps;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CamionPlanMantenimiento extends Model implements Auditable
{
    use HasFactory, Userstamps, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'camion_plan_mantenimientos';

    protected $fillable = [
        'camion_id',
        'tarea',
        'intervalo_km',
        'ultimo_km',
        'notas',
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

    public function camion()
    {
        return $this->belongsTo(Camion::class, 'camion_id');
    }

    // km recorridos desde la última realización
    public function getKmRecorridosAttribute()
    {
        return max(0, ($this->camion->kilometraje_actual ?? 0) - $this->ultimo_km);
    }

    // 'vencido' | 'proximo' (80% del intervalo) | 'ok'
    public function getEstadoAttribute()
    {
        if ($this->km_recorridos >= $this->intervalo_km) return 'vencido';
        if ($this->km_recorridos >= $this->intervalo_km * 0.8) return 'proximo';
        return 'ok';
    }
}
