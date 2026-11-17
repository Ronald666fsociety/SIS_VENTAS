<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Límite general por usuario (o IP)
        RateLimiter::for('api', fn (Request $r) => Limit::perMinute(120)->by($r->user()?->id ?: $r->ip()));

        // Login / recuperación: por teléfono + IP para frenar fuerza bruta
        RateLimiter::for('login', fn (Request $r) => Limit::perMinute(5)->by(strtolower((string) $r->input('telefono')).'|'.$r->ip()));

        // Subida de archivos (comprobantes, fotos de entrega, imágenes)
        RateLimiter::for('uploads', fn (Request $r) => Limit::perMinute(10)->by($r->user()?->id ?: $r->ip()));

        // Creación de pedidos
        RateLimiter::for('pedidos', fn (Request $r) => Limit::perMinute(6)->by($r->user()?->id ?: $r->ip()));

        // Código de entrega: evita adivinar el PIN de 4 dígitos
        RateLimiter::for('entrega', function (Request $r) {
            $pedido = $r->route('pedido');
            $id = is_object($pedido) ? $pedido->getKey() : $pedido;

            return Limit::perMinute(5)->by(($r->user()?->id ?: $r->ip()).'|'.$id);
        });
    }
}
