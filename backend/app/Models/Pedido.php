<?php

namespace App\Models;

use App\Enums\EstadoPedido;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pedido extends Model
{
    protected $table = 'pedidos';

    protected $fillable = [
        'codigo', 'user_id', 'direccion_id', 'direccion_texto', 'referencia', 'lat', 'lng',
        'repartidor_lat', 'repartidor_lng', 'repartidor_ubicacion_at',
        'zona_id', 'repartidor_id', 'subtotal', 'costo_envio', 'descuento', 'total', 'estado',
        'notas_cliente', 'codigo_entrega', 'entregado_en', 'foto_entrega_url',
        'calificacion', 'comentario_calificacion', 'calificado_at',
    ];

    protected $hidden = ['codigo_entrega', 'foto_entrega_url'];

    protected $casts = [
        'estado' => EstadoPedido::class,
        'lat' => 'float',
        'lng' => 'float',
        'repartidor_lat' => 'float',
        'repartidor_lng' => 'float',
        'repartidor_ubicacion_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'costo_envio' => 'decimal:2',
        'descuento' => 'decimal:2',
        'total' => 'decimal:2',
        'entregado_en' => 'datetime',
        'calificacion' => 'integer',
        'calificado_at' => 'datetime',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function repartidor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'repartidor_id');
    }

    public function zona(): BelongsTo
    {
        return $this->belongsTo(ZonaEnvio::class, 'zona_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PedidoItem::class);
    }

    public function pago(): HasOne
    {
        return $this->hasOne(Pago::class);
    }

    public function estados(): HasMany
    {
        return $this->hasMany(PedidoEstado::class)->orderBy('created_at')->orderBy('id');
    }
}
