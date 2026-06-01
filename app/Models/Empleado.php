<?php

namespace App\Models;

use Illuminate\Support\Str;
use Wildside\Userstamps\Userstamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Empleado extends Model
{
    use SoftDeletes, Userstamps;

    protected $table = 'empleados';

    protected $fillable = [
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'ci',
        'cargo_id',
        'telefono',
        'email',
        'activo',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($m) => $m->uuid = Str::uuid()->toString());
    }

    public function getNombreCompletoAttribute(): string
    {
        return $this->nombre . ' ' . $this->apellido_paterno . ' ' . $this->apellido_materno;
    }

    // Relación con Parametro (Cargo)
    public function cargo()
    {
        return $this->belongsTo(Parametro::class, 'cargo_id');
    }

    // Relación polimórfica - cuentas bancarias
    public function cuentasBancarias()
    {
        return $this->morphMany(CuentaBancaria::class, 'titular');
    }

    // Relación con Usuario
    public function usuario()
    {
        return $this->hasOne(User::class, 'empleado_id');
    }

    /**
     * Verifica si el empleado está siendo utilizado en otras tablas
     * @return array ['enUso' => bool, 'mensaje' => string, 'detalles' => array]
     */
    public function verificarUso()
    {
        $enUso = false;
        $mensaje = '';
        $detalles = [];

        // Verificar cuentas bancarias
        $cuentasCount = $this->cuentasBancarias()->count();
        if ($cuentasCount > 0) {
            $enUso = true;
            $detalles[] = "{$cuentasCount} cuenta(s) bancaria(s)";
        }

        // Verificar usuario asociado
        if ($this->usuario) {
            $enUso = true;
            $detalles[] = "usuario del sistema ({$this->usuario->email})";
        }

        // Aquí se pueden agregar más validaciones según otras relaciones
        // Por ejemplo: pagos, contratos, etc.

        if ($enUso) {
            $mensaje = "No se puede eliminar a '{$this->nombre_completo}' porque está siendo utilizado por: " . implode(', ', $detalles) . ".";
        }

        return [
            'enUso' => $enUso,
            'mensaje' => $mensaje,
            'detalles' => $detalles,
        ];
    }
}
