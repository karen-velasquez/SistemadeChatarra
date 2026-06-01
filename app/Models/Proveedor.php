<?php
namespace App\Models;
use Wildside\Userstamps\Userstamps;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Proveedor extends Model implements Auditable
{
    use HasFactory, Userstamps, SoftDeletes;
    use \OwenIt\Auditing\Auditable;
    protected $fillable = [
        'nombre',
        'nit',
        'pais_id',
        'email',
        'tipo_producto',
        'created_by',
        'updated_by',
        'deleted_by',

    ];

    public function pais()
    {
        return $this->belongsTo(Parametro::class, 'pais_id');
    }

    public function contacts()
    {
        return $this->morphMany(\App\Models\Contacto::class, 'contactable');
    }
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid()->toString();
        });
    }
}
