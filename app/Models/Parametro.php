<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Wildside\Userstamps\Userstamps;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Parametro extends Model implements Auditable
{
    use HasFactory, Userstamps, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'uuid',
        'tipo',
        'descripcion',
        'valor',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid()->toString();
        });
    }

    // Scope para obtener parámetros por tipo
    public function scopeTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    // Relaciones inversas para verificar uso
    public function empleadosCargo()
    {
        return $this->hasMany(\App\Models\Empleado::class, 'cargo_id');
    }

    public function usuariosLugarTrabajo()
    {
        return $this->hasMany(\App\Models\User::class, 'lugar_trabajo_id');
    }

    /**
     * Verifica si el parámetro está siendo utilizado en alguna relación
     * @return array ['enUso' => bool, 'mensaje' => string, 'detalles' => array]
     */
    public function verificarUso()
    {
        $enUso = false;
        $mensaje = '';
        $detalles = [];

        // Mapa de validaciones por tipo de parámetro
        $validaciones = [
            'cargo_empleados' => [
                'relacion' => 'empleadosCargo',
                'nombre' => 'empleado(s)',
            ],
            'lugar_trabajo' => [
                'relacion' => 'usuariosLugarTrabajo',
                'nombre' => 'usuario(s)',
            ],
            'sucursal_cuenta' => [
                'tabla' => 'cuentas_bancarias',
                'columna' => 'sucursal_departamento',
                'buscar_por' => 'descripcion', // busca por el campo descripcion del parámetro
                'nombre' => 'cuenta(s) bancaria(s)',
            ],
        ];

        // Verificar si existe una validación para este tipo
        if (isset($validaciones[$this->tipo])) {
            $config = $validaciones[$this->tipo];

            // Validación por relación (foreign key)
            if (isset($config['relacion'])) {
                $relacion = $config['relacion'];
                if (method_exists($this, $relacion)) {
                    $count = $this->$relacion()->count();
                    if ($count > 0) {
                        $enUso = true;
                        $detalles[] = "{$count} {$config['nombre']}";
                    }
                }
            }
            // Validación por búsqueda de texto en columna
            elseif (isset($config['tabla']) && isset($config['columna'])) {
                $valorBuscar = $this->{$config['buscar_por'] ?? 'valor'};
                $count = \DB::table($config['tabla'])
                    ->where($config['columna'], $valorBuscar)
                    ->whereNull('deleted_at')
                    ->count();

                if ($count > 0) {
                    $enUso = true;
                    $detalles[] = "{$count} {$config['nombre']}";
                }
            }
        } else {
            // Si no hay validación específica, buscar en todas las foreign keys
            $fks = $this->buscarForeignKeys();
            foreach ($fks as $fk) {
                $count = \DB::table($fk['tabla'])->where($fk['columna'], $this->id)->count();
                if ($count > 0) {
                    $enUso = true;
                    $detalles[] = "{$count} registro(s) en {$fk['tabla']}";
                }
            }
        }

        if ($enUso) {
            // Para sucursal_cuenta, mostrar la descripción en lugar del valor
            $nombreParametro = $this->tipo === 'sucursal_cuenta' ? $this->descripcion : $this->valor;
            $mensaje = "No se puede eliminar '{$nombreParametro}' porque está siendo utilizado por: " . implode(', ', $detalles) . ".";
        }

        return [
            'enUso' => $enUso,
            'mensaje' => $mensaje,
            'detalles' => $detalles,
        ];
    }

    /**
     * Busca todas las foreign keys que referencian a la tabla parametros
     * @return array
     */
    private function buscarForeignKeys()
    {
        $db = config('database.connections.mysql.database');

        $fks = \DB::select("
            SELECT
                TABLE_NAME as tabla,
                COLUMN_NAME as columna
            FROM
                INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE
                REFERENCED_TABLE_SCHEMA = ?
                AND REFERENCED_TABLE_NAME = 'parametros'
        ", [$db]);

        return array_map(function($fk) {
            return [
                'tabla' => $fk->tabla,
                'columna' => $fk->columna,
            ];
        }, $fks);
    }
}

