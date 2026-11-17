<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Auditoria extends Model
{
    protected $table = 'auditoria';
    public $timestamps = false;
    protected $fillable = ['user_id', 'accion', 'entidad', 'entidad_id', 'detalle', 'ip'];
    protected $casts = ['detalle' => 'array', 'created_at' => 'datetime'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
