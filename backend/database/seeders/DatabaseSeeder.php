<?php

namespace Database\Seeders;

use App\Enums\Rol;
use App\Models\AppVersion;
use App\Models\Categoria;
use App\Models\ConfiguracionPago;
use App\Models\Producto;
use App\Models\User;
use App\Models\ZonaEnvio;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Datos mínimos para cualquier entorno
        AppVersion::updateOrCreate(['app' => 'cliente'], ['version_minima' => '1.0.0']);
        AppVersion::updateOrCreate(['app' => 'negocio'], ['version_minima' => '1.0.0']);

        // Datos de demostración y cuentas de prueba por rol (útiles para la revisión de Apple/Google)
        if (app()->environment(['local', 'testing', 'staging'])) {
            $this->demo();
        }
    }

    private function demo(): void
    {
        $cuentas = [
            ['Admin', '70000001', Rol::Admin],
            ['Operador', '70000002', Rol::Operador],
            ['Repartidor', '70000003', Rol::Repartidor],
            ['Cliente', '70000004', Rol::Cliente],
        ];
        foreach ($cuentas as [$nombre, $telefono, $rol]) {
            User::firstOrCreate(['telefono' => $telefono], [
                'nombre' => "$nombre Demo",
                'password' => 'password123',
                'rol' => $rol,
            ]);
        }

        ZonaEnvio::firstOrCreate(['nombre' => 'Centro'], [
            'costo_envio' => 8, 'pedido_minimo' => 30,
            'centro_lat' => -17.783300, 'centro_lng' => -63.182100, 'radio_km' => 5,
        ]);

        ZonaEnvio::firstOrCreate(['nombre' => 'Toda Bolivia (Nacional)'], [
            'costo_envio' => 25, 'pedido_minimo' => 30,
            'centro_lat' => -17.393500, 'centro_lng' => -66.157000, 'radio_km' => 1200,
        ]);

        ConfiguracionPago::updateOrCreate(['id' => 1], [
            'banco' => 'Banco Demo',
            'titular' => 'Tienda Demo',
            'nro_cuenta' => '000123456',
            'qr_imagen_url' => 'https://example.com/qr-demo.png',
            'instrucciones' => '1. Paga desde tu banco · 2. Toma captura del comprobante · 3. Súbela aquí.',
            'updated_at' => now(),
        ]);

        $bebidas = Categoria::firstOrCreate(['slug' => 'bebidas'], ['nombre' => 'Bebidas', 'orden' => 1]);
        $snacks = Categoria::firstOrCreate(['slug' => 'snacks'], ['nombre' => 'Snacks', 'orden' => 2]);

        foreach ([
            [$bebidas, 'Agua mineral 2L', 'BEB-001', 8.00, null, 100, true],
            [$bebidas, 'Gaseosa 2L', 'BEB-002', 14.00, 12.00, 60, false],
            [$snacks, 'Papas fritas 150g', 'SNK-001', 10.00, null, 80, true],
            [$snacks, 'Galletas surtidas', 'SNK-002', 6.50, null, 40, false],
        ] as [$cat, $nombre, $sku, $precio, $oferta, $stock, $destacado]) {
            Producto::firstOrCreate(['sku' => $sku], [
                'categoria_id' => $cat->id, 'nombre' => $nombre, 'precio' => $precio,
                'precio_oferta' => $oferta, 'stock' => $stock, 'stock_minimo' => 5, 'destacado' => $destacado,
            ]);
        }
    }
}
