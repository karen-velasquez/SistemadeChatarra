<?php

namespace App\Models;

use Illuminate\Support\Str;
use Wildside\Userstamps\Userstamps;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdquisicionFactura extends Model implements Auditable
{
    use HasFactory, Userstamps, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'adquisicion_facturas';

    protected $fillable = [
        'adquisicion_id',
        'numero',
        'fecha',
        'emisor',
        'monto',
        'moneda',
        'archivo',
        'observaciones',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid()->toString();
        });
    }

    public function adquisicion()
    {
        return $this->belongsTo(Adquisicion::class, 'adquisicion_id');
    }
}
