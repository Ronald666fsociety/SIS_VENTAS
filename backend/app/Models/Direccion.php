<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Direccion extends Model
{
    protected $table = 'direcciones';
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'alias', 'direccion', 'referencia', 'lat', 'lng', 'zona_id', 'es_principal',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'es_principal' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function zona(): BelongsTo
    {
        return $this->belongsTo(ZonaEnvio::class, 'zona_id');
    }
}
