<?php

namespace App\Models;

use Illuminate\Support\Str;
use Wildside\Userstamps\Userstamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\CuentaEmpresa;

class PagoCliente extends Model
{
    use SoftDeletes, Userstamps;

    protected $table = 'pagos_cliente';

    protected $fillable = [
        'tramo_id',
        'tipo_pago',
        'monto',
        'moneda_pago',
        'tipo_cambio',
        'fecha_pago',
        'metodo_pago',
        'codigo_seguimiento',
        'cuenta_origen_id',
        'cuenta_destino_id',
        'observaciones',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'monto'       => 'decimal:2',
        'tipo_cambio' => 'decimal:4',
        'fecha_pago'  => 'date',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($m) => $m->uuid = Str::uuid()->toString());
    }

    public function tramo()
    {
        return $this->belongsTo(Tramo::class, 'tramo_id');
    }

    public function cuentaOrigen()
    {
        return $this->belongsTo(CuentaBancaria::class, 'cuenta_origen_id');
    }

    public function cuentaDestino()
    {
        return $this->belongsTo(CuentaEmpresa::class, 'cuenta_destino_id');
    }

    public function getTipoPagoLabelAttribute(): string
    {
        return match($this->tipo_pago) {
            'adelanto'   => 'Adelanto',
            'parcial'    => 'Parcial',
            'pago_final' => 'Pago Final',
            default      => $this->tipo_pago,
        };
    }
}
