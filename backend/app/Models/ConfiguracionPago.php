<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracionPago extends Model
{
    protected $table = 'configuracion_pago';
    public $timestamps = false;
    public $incrementing = false;
    protected $fillable = ['id', 'banco', 'titular', 'nro_cuenta', 'qr_imagen_url', 'instrucciones', 'aceptar_efectivo', 'updated_at'];
    protected $casts = [
        'aceptar_efectivo' => 'boolean',
        'updated_at' => 'datetime',
    ];

    public static function actual(): ?self
    {
        return static::query()->find(1);
    }
}
