<?php

namespace App\Models;

use Illuminate\Support\Str;
use Wildside\Userstamps\Userstamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Banco extends Model
{
    use SoftDeletes, Userstamps;

    protected $table = 'bancos';

    protected $fillable = [
        'nombre', 'pais_id', 'codigo_swift', 'codigo_banco', 'activo',
        'created_by', 'updated_by',
    ];

    protected $casts = ['activo' => 'boolean'];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($m) => $m->uuid = Str::uuid()->toString());
    }

    public function pais()
    {
        return $this->belongsTo(Parametro::class, 'pais_id');
    }

    public function cuentas()
    {
        return $this->hasMany(CuentaBancaria::class, 'banco_id');
    }
}
