<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ContratoDocumento extends Model
{
    use SoftDeletes;

    protected $table = 'contrato_documentos';

    protected $fillable = [
        'contrato_id',
        'archivo',
        'nombre_original',
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

    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }
}
