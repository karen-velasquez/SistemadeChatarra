<?php

namespace App\Models;

use Illuminate\Support\Str;
use Wildside\Userstamps\Userstamps;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdquisicionCuota extends Model implements Auditable
{
    use HasFactory, Userstamps, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'adquisicion_cuotas';

    protected $fillable = [
        'adquisicion_id',
        'nro',
        'fecha_programada',
        'monto',
        'tasa_aplicada',
        'fecha_pago',
        'monto_pagado',
        'tipo_cambio',
        'monto_pagado_bob',
        'comprobante',
        'observaciones',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'fecha_programada' => 'date',
        'fecha_pago'       => 'date',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid()->toString();
        });
    }

    public function adquisicion()
    {
        return $this->belongsTo(Adquisicion::class, 'adquisicion_id');
    }

    // 'PAGADO' | 'ATRASADO' | 'PENDIENTE'
    public function getEstadoAttribute()
    {
        if ($this->fecha_pago) return 'PAGADO';
        if ($this->fecha_programada->lt(now()->startOfDay())) return 'ATRASADO';
        return 'PENDIENTE';
    }

    public function getDiasAtrasoAttribute()
    {
        if ($this->estado !== 'ATRASADO') return 0;
        return $this->fecha_programada->diffInDays(now()->startOfDay());
    }

    // % anual vigente para esta cuota (la propia si es interés variable, si no la del crédito)
    public function getTasaVigenteAttribute()
    {
        return $this->tasa_aplicada ?? $this->adquisicion->tasa_interes ?? 0;
    }

    // interés de mora por los días de atraso (tasa anual / 360)
    public function getInteresMoraAttribute()
    {
        return round($this->monto * ($this->tasa_vigente / 100) / 360 * $this->dias_atraso, 2);
    }

    // deuda del mes: cuota + interés acumulado por atraso
    public function getDeudaAttribute()
    {
        return $this->monto + $this->interes_mora;
    }
}
