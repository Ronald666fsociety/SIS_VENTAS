<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZonaEnvio extends Model
{
    protected $table = 'zonas_envio';
    public $timestamps = false;

    protected $fillable = [
        'nombre', 'costo_envio', 'pedido_minimo', 'poligono',
        'centro_lat', 'centro_lng', 'radio_km', 'activa',
    ];

    protected $casts = [
        'poligono' => 'array',
        'costo_envio' => 'decimal:2',
        'pedido_minimo' => 'decimal:2',
        'centro_lat' => 'float',
        'centro_lng' => 'float',
        'radio_km' => 'float',
        'activa' => 'boolean',
    ];
}
