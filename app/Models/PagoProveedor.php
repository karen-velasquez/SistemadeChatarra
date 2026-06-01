<?php

namespace App\Models;

use Illuminate\Support\Str;
use Wildside\Userstamps\Userstamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\CuentaEmpresa;

class PagoProveedor extends Model
{
    use SoftDeletes, Userstamps;

    protected $table = 'pagos_proveedor';

    protected $fillable = [
        'lote_pago_id',
        'contrato_id',
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

    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }

    public function cuentaOrigen()
    {
        return $this->belongsTo(CuentaEmpresa::class, 'cuenta_origen_id');
    }

    public function cuentaDestino()
    {
        return $this->belongsTo(CuentaBancaria::class, 'cuenta_destino_id');
    }

    // Monto convertido a la moneda del contrato para calcular saldo
    public function getMontoEnMonedaContratoAttribute(): float
    {
        $monedaContrato = $this->contrato->moneda ?? 'BOB';
        $monto = (float) $this->monto;
        $tc    = (float) $this->tipo_cambio ?: 1;

        if ($this->moneda_pago === $monedaContrato) {
            return $monto;
        }
        // Si pagué en BOB y el contrato es en otra moneda, divido por TC
        if ($monedaContrato !== 'BOB' && $this->moneda_pago === 'BOB') {
            return $tc > 0 ? round($monto / $tc, 2) : 0;
        }
        // Cualquier otro caso: convierto a BOB
        return round($monto * $tc, 2);
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
