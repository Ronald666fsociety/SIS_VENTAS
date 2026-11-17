<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    protected $table = 'categorias';
    public $timestamps = false;
    protected $fillable = ['nombre', 'slug', 'imagen_url', 'orden', 'activa'];
    protected $casts = ['activa' => 'boolean', 'orden' => 'integer'];

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }
}
