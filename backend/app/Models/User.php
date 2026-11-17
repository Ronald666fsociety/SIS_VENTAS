<?php

namespace App\Models;

use App\Enums\Rol;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'nombre', 'apellido', 'email', 'telefono', 'password', 'rol', 'activo', 'avatar_url',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'rol' => Rol::class,
            'activo' => 'boolean',
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
            'telefono_verified_at' => 'datetime',
        ];
    }

    public function tieneRol(Rol ...$roles): bool
    {
        return in_array($this->rol, $roles, true);
    }

    public function esPersonal(): bool
    {
        return $this->rol->esPersonal();
    }

    public function direcciones(): HasMany
    {
        return $this->hasMany(Direccion::class);
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class);
    }

    public function dispositivos(): HasMany
    {
        return $this->hasMany(Dispositivo::class);
    }

    public function favoritos()
    {
        return $this->belongsToMany(Producto::class, 'favoritos', 'user_id', 'producto_id');
    }
}
