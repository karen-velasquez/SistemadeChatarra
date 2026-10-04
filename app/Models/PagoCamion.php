<?php

namespace App\Models;

use Illuminate\Support\Str;
use Wildside\Userstamps\Userstamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\CuentaEmpresa;

class PagoCamion extends Model
{
    use SoftDeletes, Userstamps;

    protected $table = 'pagos_camion';

    protected $fillable = [
        'lote_pago_id',
        'contrato_camion_id',
        'tipo_pago',
        'monto',
        'moneda_pago',
        'tipo_cambio',
        'fecha_pago',
        'receptor_type',
        'receptor_id',
        'cuenta_origen_id',
        'cuenta_destino_id',
        'metodo_pago',
        'codigo_seguimiento',
        'observaciones',
        'voucher',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'monto'       => 'decimal:2',
        'tipo_cambio' => 'decimal:4',
        'fecha_pago'  => 'date',
    ];

    // Monto equivalente en BOB (para cálculos de saldo)
    public function getMontoEnBobAttribute(): float
    {
        return round((float)$this->monto * (float)$this->tipo_cambio, 2);
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($m) => $m->uuid = Str::uuid()->toString());
    }

    public function contratoCamion()
    {
        return $this->belongsTo(ContratoCamion::class, 'contrato_camion_id');
    }

    public function receptor()
    {
        return $this->morphTo();
    }

    public function cuentaOrigen()
    {
        return $this->belongsTo(CuentaEmpresa::class, 'cuenta_origen_id');
    }

    public function cuentaDestino()
    {
        return $this->belongsTo(CuentaBancaria::class, 'cuenta_destino_id');
    }

    public function usuarioCreador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function usuarioActualizador()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function usuarioEliminador()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function getNombreReceptorAttribute(): string
    {
        if (!$this->receptor) return '—';
        return $this->receptor->nombre_completo ?? $this->receptor->nombre ?? '—';
    }

    public function getTipoPagoLabelAttribute(): string
    {
        return match($this->tipo_pago) {
            'adelanto'    => 'Adelanto',
            'flete'       => 'Flete',
            'pago_final'  => 'Pago Final',
            default       => $this->tipo_pago,
        };
    }
}
