<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedidoItem extends Model
{
    protected $table = 'pedido_items';
    public $timestamps = false;
    protected $fillable = ['pedido_id', 'producto_id', 'nombre_producto', 'precio_unitario', 'cantidad', 'subtotal'];
    protected $casts = ['precio_unitario' => 'decimal:2', 'subtotal' => 'decimal:2', 'cantidad' => 'integer'];
}
