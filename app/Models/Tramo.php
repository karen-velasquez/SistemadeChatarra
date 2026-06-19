<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Wildside\Userstamps\Userstamps;
use App\Models\Cliente;

class Tramo extends Model
{
    use SoftDeletes, Userstamps;

    protected $table = 'tramos';

    protected $fillable = [
        'contrato_camion_id',
        'tramo_padre_id',
        'camion_id',
        'conductor_id',
        'cliente_id',
        'direccion_entrega',
        'empresa_facturadora_id',
        'origen',
        'destino',
        'tipo_tramo',
        'peso_declarado',
        'peso_salida',
        'peso_llegada',
        'precio_por_tonelada',
        'moneda_venta',
        'descuento_porcentaje',
        'observaciones_llegada',
        'documento_entrega',
        'fecha_salida',
        'fecha_llegada',
        'estado',
        'activo',
        'observaciones',
        'lote_entrega_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'peso_declarado'      => 'decimal:3',
        'peso_salida'         => 'decimal:3',
        'peso_llegada'        => 'decimal:3',
        'precio_por_tonelada' => 'decimal:4',
        'fecha_salida'        => 'date',
        'fecha_llegada'       => 'date',
        'activo'              => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($m) => $m->uuid = Str::uuid()->toString());
    }

    // El camión asignado al contrato al que pertenece este tramo
    public function contratoCamion()
    {
        return $this->belongsTo(ContratoCamion::class);
    }

    // Tramo anterior del que se originó (transbordo)
    public function tramoPadre()
    {
        return $this->belongsTo(Tramo::class, 'tramo_padre_id');
    }

    // Tramos hijos generados por transbordo en este tramo
    public function tramosHijos()
    {
        return $this->hasMany(Tramo::class, 'tramo_padre_id');
    }

    public function camion()
    {
        return $this->belongsTo(Camion::class);
    }

    public function conductor()
    {
        return $this->belongsTo(OperadorTransporte::class, 'conductor_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function pagosCliente()
    {
        return $this->hasMany(PagoCliente::class, 'tramo_id');
    }

    public function loteEntrega()
    {
        return $this->belongsTo(LoteEntrega::class, 'lote_entrega_id');
    }

    // Monto total que debe el cliente por esta entrega
    public function getMontoDeudaClienteAttribute(): float
    {
        if (!$this->precio_por_tonelada || !$this->peso_llegada) return 0;
        return round((float)$this->peso_llegada * (float)$this->precio_por_tonelada, 2);
    }

    // Total ya cobrado al cliente en moneda de venta
    public function getTotalCobradoClienteAttribute(): float
    {
        $moneda = $this->moneda_venta ?? 'BOB';
        return (float) $this->pagosCliente()
            ->whereNull('deleted_at')
            ->get()
            ->sum(function ($p) use ($moneda) {
                $monto = (float) $p->monto;
                $tc    = (float) $p->tipo_cambio ?: 1;
                if ($p->moneda_pago === $moneda) return $monto;
                if ($moneda !== 'BOB' && $p->moneda_pago === 'BOB') return $tc > 0 ? $monto / $tc : 0;
                return round($monto * $tc, 2);
            });
    }

    public function getSaldoClienteAttribute(): float
    {
        return max(0, $this->monto_deuda_cliente - $this->total_cobrado_cliente);
    }

    // Un tramo es "final" si no tiene hijos (llega al cliente)
    public function esFinal(): bool
    {
        return $this->tramosHijos()->count() === 0;
    }
}
