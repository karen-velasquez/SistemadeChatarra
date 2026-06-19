<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PagoExtraLote extends Model
{
    use SoftDeletes;

    protected $table = 'pagos_extras_lote';

    protected $fillable = [
        'lote_entrega_id',
        'cuenta_origen_id',
        'monto',
        'moneda',
        'tipo_cambio',
        'monto_bolivianos',
        'fecha',
        'metodo_pago',
        'codigo_seguimiento',
        'descripcion',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha'            => 'date',
        'monto'            => 'float',
        'tipo_cambio'      => 'float',
        'monto_bolivianos' => 'float',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($m) => $m->uuid = Str::uuid()->toString());
    }

    public function loteEntrega()
    {
        return $this->belongsTo(LoteEntrega::class, 'lote_entrega_id');
    }

    public function cuentaOrigen()
    {
        return $this->belongsTo(CuentaEmpresa::class, 'cuenta_origen_id');
    }

    public function movimiento()
    {
        return $this->morphOne(Movimiento::class, 'origen');
    }
}
