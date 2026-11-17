<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    protected $table = 'notificaciones';
    public $timestamps = false;
    protected $fillable = ['user_id', 'titulo', 'mensaje', 'pedido_id', 'leida'];
    protected $casts = ['leida' => 'boolean', 'created_at' => 'datetime'];
}
