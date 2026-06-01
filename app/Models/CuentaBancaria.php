<?php

namespace App\Models;

use Illuminate\Support\Str;
use Wildside\Userstamps\Userstamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CuentaBancaria extends Model
{
    use SoftDeletes, Userstamps;

    protected $table = 'cuentas_bancarias';

    protected $fillable = [
        'banco_id', 'tipo_titular', 'titular_id', 'titular_type',
        'numero_cuenta', 'moneda', 'alias',
        'nombre_titular', 'apellido_paterno_titular', 'apellido_materno_titular',
        'nro_documento', 'email_notificacion', 'sucursal_departamento', 'tipo_relacion', 'activo',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'activo' => 'boolean'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(fn($m) => $m->uuid = Str::uuid()->toString());
    }

    public function banco()
    {
        return $this->belongsTo(Banco::class, 'banco_id');
    }

    public function titular()
    {
        return $this->morphTo();
    }

    public function getNombreTitularDisplayAttribute(): string
    {
        $nombreManual = $this->attributes['nombre_titular'] ?? null;

        if ($nombreManual) {
            $ap = trim(($this->attributes['apellido_paterno_titular'] ?? '') . ' ' . ($this->attributes['apellido_materno_titular'] ?? ''));
            return trim($nombreManual . ' ' . $ap);
        }

        return match($this->tipo_titular) {
            'proveedor' => $this->titular?->nombre ?? '—',
            'operador'  => $this->titular?->nombre_completo ?? '—',
            'empleado'  => $this->titular?->nombre_completo ?? '—',
            'cliente'   => $this->titular?->nombre ?? '—',
            default     => '—',
        };
    }

    // Formato para Excel: APELLIDO_PATERNO APELLIDO_MATERNO NOMBRE
    public function getNombreTitularExcelAttribute(): string
    {
        $nombreManual = $this->attributes['nombre_titular'] ?? null;

        if ($nombreManual) {
            return trim(
                ($this->attributes['apellido_paterno_titular'] ?? '') . ' ' .
                ($this->attributes['apellido_materno_titular'] ?? '') . ' ' .
                $nombreManual
            );
        }

        $t = $this->titular;
        return match($this->tipo_titular) {
            'operador', 'empleado' => $t
                ? trim(($t->apellido_paterno ?? '') . ' ' . ($t->apellido_materno ?? '') . ' ' . ($t->nombre ?? ''))
                : '—',
            'proveedor' => $t?->nombre ?? '—',
            'cliente'   => $t?->nombre ?? '—',
            default     => '—',
        };
    }
}