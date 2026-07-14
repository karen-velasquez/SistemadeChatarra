<?php

namespace App\Models;

use Illuminate\Support\Str;
use Wildside\Userstamps\Userstamps;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Adquisicion extends Model implements Auditable
{
    use HasFactory, Userstamps, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'adquisiciones';

    protected $fillable = [
        'descripcion',
        'tipo_bien_id',
        'camion_id',
        'origen',
        'pais_origen_id',
        'vendedor',
        'financiamiento',
        'entidad_financiera',
        'moneda',
        'monto_total',
        'tipo_interes',
        'tasa_interes',
        'fecha_adquisicion',
        'fecha_contrato_inicio',
        'fecha_contrato_fin',
        'observaciones',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'fecha_adquisicion'     => 'date',
        'fecha_contrato_inicio' => 'date',
        'fecha_contrato_fin'    => 'date',
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

    public function tipoBien()
    {
        return $this->belongsTo(Parametro::class, 'tipo_bien_id');
    }

    public function paisOrigen()
    {
        return $this->belongsTo(Parametro::class, 'pais_origen_id');
    }

    public function cuotas()
    {
        return $this->hasMany(AdquisicionCuota::class, 'adquisicion_id')->orderBy('nro');
    }

    public function facturas()
    {
        return $this->hasMany(AdquisicionFactura::class, 'adquisicion_id')->orderByDesc('fecha');
    }

    // en la moneda de la adquisición
    public function getSaldoPendienteAttribute()
    {
        return $this->cuotas->whereNull('fecha_pago')->sum('monto');
    }

    public function getTotalPagadoBobAttribute()
    {
        return $this->cuotas->whereNotNull('fecha_pago')->sum('monto_pagado_bob');
    }

    // 'SIN PLAN' | 'EN CURSO' | 'ATRASADO' | 'PAGADO'
    public function getEstadoAttribute()
    {
        if ($this->cuotas->isEmpty()) return 'SIN PLAN';
        if ($this->cuotas->whereNull('fecha_pago')->isEmpty()) return 'PAGADO';
        if ($this->cuotas->contains(fn($c) => $c->estado === 'ATRASADO')) return 'ATRASADO';
        return 'EN CURSO';
    }
}
