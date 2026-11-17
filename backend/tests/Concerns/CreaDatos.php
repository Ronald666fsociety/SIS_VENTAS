<?php

namespace Tests\Concerns;

use App\Enums\Rol;
use App\Models\Categoria;
use App\Models\ConfiguracionPago;
use App\Models\Producto;
use App\Models\User;
use App\Models\ZonaEnvio;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

trait CreaDatos
{
    protected function prepararDiscos(): void
    {
        Storage::fake('local');
        Storage::fake('public');
    }

    protected function usuario(Rol $rol = Rol::Cliente): User
    {
        return User::factory()->rol($rol)->create();
    }

    /** Autentica como el usuario con la habilidad de su app. */
    protected function como(User $user): User
    {
        Sanctum::actingAs($user, [$user->esPersonal() ? 'negocio' : 'cliente']);

        return $user;
    }

    protected function zonaCentro(array $extra = []): ZonaEnvio
    {
        return ZonaEnvio::create($extra + [
            'nombre' => 'Centro', 'costo_envio' => 8, 'pedido_minimo' => 20,
            'centro_lat' => -17.7833, 'centro_lng' => -63.1821, 'radio_km' => 5,
        ]);
    }

    protected function producto(float $precio = 10, int $stock = 50, array $extra = []): Producto
    {
        $cat = Categoria::first() ?? Categoria::create(['nombre' => 'General', 'slug' => 'general']);

        return Producto::create($extra + [
            'categoria_id' => $cat->id,
            'nombre' => 'Producto '.uniqid(),
            'precio' => $precio,
            'stock' => $stock,
            'stock_minimo' => 2,
        ]);
    }

    protected function configuracionPago(): void
    {
        ConfiguracionPago::create(['id' => 1, 'banco' => 'B', 'titular' => 'T', 'qr_imagen_url' => 'http://x/qr.png']);
    }

    protected function cuerpoPedido(Producto $p, int $cantidad = 3, array $extra = []): array
    {
        return $extra + [
            'items' => [['producto_id' => $p->id, 'cantidad' => $cantidad]],
            'direccion_texto' => 'Av. Principal 123',
            'referencia' => 'Portón verde',
            'lat' => -17.7840,
            'lng' => -63.1830,
        ];
    }
}
