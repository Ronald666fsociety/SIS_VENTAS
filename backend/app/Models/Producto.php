<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use SoftDeletes;

    protected $table = 'productos';

    protected $fillable = [
        'categoria_id', 'nombre', 'descripcion', 'sku', 'precio', 'precio_oferta',
        'unidad', 'stock', 'stock_minimo', 'activo', 'destacado',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'precio_oferta' => 'decimal:2',
        'stock' => 'integer',
        'stock_minimo' => 'integer',
        'activo' => 'boolean',
        'destacado' => 'boolean',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function imagenes(): HasMany
    {
        return $this->hasMany(ProductoImagen::class)->orderBy('orden');
    }

    public function scopeVisibles(Builder $q): Builder
    {
        return $q->where('activo', true);
    }

    /** Precio efectivo: oferta si existe y es menor que el precio normal. */
    public function precioFinal(): string
    {
        if ($this->precio_oferta !== null && bccomp((string) $this->precio_oferta, (string) $this->precio, 2) < 0) {
            return (string) $this->precio_oferta;
        }
        return (string) $this->precio;
    }
}
