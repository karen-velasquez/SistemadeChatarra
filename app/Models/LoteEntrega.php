<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LoteEntrega extends Model
{
    protected $table = 'lotes_entrega';

    protected $fillable = [
        'codigo',
        'proveedor_id',
        'numero_semana',
        'anio',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'observaciones',
        'cerrado_at',
        'cerrado_by',
        'created_by',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
        'cerrado_at'   => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($m) {
            $m->uuid = Str::uuid()->toString();

            // Generar código correlativo: LE-{SIGLA}-{NNN}
            if (empty($m->codigo)) {
                $proveedor = Proveedor::find($m->proveedor_id);
                $sigla     = self::siglaProveedor($proveedor?->nombre ?? 'XX');
                $ultimo    = self::where('proveedor_id', $m->proveedor_id)
                    ->whereNotNull('codigo')
                    ->orderByDesc('id')
                    ->value('codigo');

                $siguiente = 1;
                if ($ultimo && preg_match('/-(\d+)$/', $ultimo, $hits)) {
                    $siguiente = (int) $hits[1] + 1;
                }

                $m->codigo = 'LE-' . $sigla . '-' . str_pad($siguiente, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Genera sigla de hasta 4 caracteres en mayúsculas a partir del nombre del proveedor.
     * "Agro Bolivia" → "AGRO", "Metales S.A." → "META", "XYZ" → "XYZ"
     */
    private static function siglaProveedor(string $nombre): string
    {
        // Quitar puntos y caracteres especiales, quedar con letras y espacios
        $limpio = preg_replace('/[^A-Za-záéíóúüñÁÉÍÓÚÜÑ\s]/u', '', $nombre);
        $palabras = array_filter(explode(' ', $limpio));

        if (count($palabras) >= 2) {
            // Tomar hasta 4 iniciales de las palabras
            $sigla = implode('', array_map(fn($p) => strtoupper(mb_substr($p, 0, 1)), array_slice($palabras, 0, 4)));
        } else {
            // Una sola palabra: primeras 4 letras
            $sigla = strtoupper(mb_substr(array_values($palabras)[0] ?? 'XX', 0, 4));
        }

        return $sigla;
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function tramos()
    {
        return $this->hasMany(Tramo::class, 'lote_entrega_id');
    }

    public function pagosExtras()
    {
        return $this->hasMany(PagoExtraLote::class, 'lote_entrega_id');
    }

    public function cerradoPor()
    {
        return $this->belongsTo(\App\Models\User::class, 'cerrado_by');
    }

    public function getNombreAttribute(): string
    {
        $cod = $this->codigo ? "[{$this->codigo}] " : '';
        return "{$cod}Semana {$this->numero_semana}/{$this->anio} — {$this->fecha_inicio->format('d/m')} al {$this->fecha_fin->format('d/m/Y')}";
    }

    /**
     * Obtiene o crea el lote de la semana actual para un proveedor.
     */
    public static function obtenerOCrearSemanaActual(int $proveedorId): self
    {
        $hoy     = now();
        $semana  = (int) $hoy->format('W');
        $anio    = (int) $hoy->format('o');
        $lunes   = $hoy->copy()->startOfWeek();
        $domingo = $hoy->copy()->endOfWeek();

        return self::firstOrCreate(
            ['proveedor_id' => $proveedorId, 'numero_semana' => $semana, 'anio' => $anio],
            [
                'fecha_inicio' => $lunes->toDateString(),
                'fecha_fin'    => $domingo->toDateString(),
                'estado'       => 'Abierto',
                'created_by'   => auth()->id(),
                // codigo se genera en el boot->creating
            ]
        );
    }

    /**
     * Devuelve los lotes abiertos del proveedor: semana actual + hasta 3 anteriores sin cerrar.
     */
    public static function lotesAbiertosParaProveedor(int $proveedorId): \Illuminate\Database\Eloquent\Collection
    {
        // Asegurar que existe el lote de esta semana
        self::obtenerOCrearSemanaActual($proveedorId);

        return self::where('proveedor_id', $proveedorId)
            ->where('estado', 'Abierto')
            ->orderByDesc('anio')
            ->orderByDesc('numero_semana')
            ->limit(4)
            ->get();
    }
}
