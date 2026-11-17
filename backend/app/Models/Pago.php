<?php

namespace App\Models;

use App\Enums\EstadoPago;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    protected $table = 'pagos';

    protected $fillable = [
        'pedido_id', 'metodo', 'monto', 'estado', 'comprobante_path', 'nro_transaccion',
        'intentos', 'motivo_rechazo', 'verificado_por', 'verificado_en',
    ];

    protected $hidden = ['comprobante_path'];

    protected $casts = [
        'estado' => EstadoPago::class,
        'monto' => 'decimal:2',
        'intentos' => 'integer',
        'verificado_en' => 'datetime',
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }
}
