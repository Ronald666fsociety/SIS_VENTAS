<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dispositivo extends Model
{
    protected $table = 'dispositivos';
    public $timestamps = false;
    protected $fillable = ['user_id', 'token', 'plataforma', 'app', 'version_app', 'ultimo_uso'];
    protected $casts = ['ultimo_uso' => 'datetime'];
}
