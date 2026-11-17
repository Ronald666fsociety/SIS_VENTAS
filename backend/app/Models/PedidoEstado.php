<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedidoEstado extends Model
{
    protected $table = 'pedido_estados';
    public $timestamps = false;
    protected $fillable = ['pedido_id', 'estado', 'nota', 'user_id', 'created_at'];
    protected $casts = ['created_at' => 'datetime'];
}
