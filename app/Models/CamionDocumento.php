<?php

namespace App\Models;

use Illuminate\Support\Str;
use Wildside\Userstamps\Userstamps;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CamionDocumento extends Model implements Auditable
{
    use HasFactory, Userstamps, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'camion_documentos';

    protected $fillable = [
        'camion_id',
        'tipo',
        'descripcion',
        'archivo',
        'fecha_emision',
        'fecha_vencimiento',
        'monto',
        'observaciones',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'fecha_emision'     => 'date',
        'fecha_vencimiento' => 'date',
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

    // 'vencido' | 'por_vencer' (30 días) | 'vigente' | null si no tiene vencimiento
    public function getEstadoVencimientoAttribute()
    {
        if (!$this->fecha_vencimiento) return null;
        $dias = now()->startOfDay()->diffInDays($this->fecha_vencimiento, false);
        if ($dias < 0)   return 'vencido';
        if ($dias <= 30) return 'por_vencer';
        return 'vigente';
    }
}
