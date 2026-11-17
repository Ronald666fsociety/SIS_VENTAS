<?php

use App\Http\Controllers\Api\V1\Admin;
use App\Http\Controllers\Api\V1\ArchivoPrivadoController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogoController;
use App\Http\Controllers\Api\V1\DireccionController;
use App\Http\Controllers\Api\V1\DispositivoController;
use App\Http\Controllers\Api\V1\NotificacionController;
use App\Http\Controllers\Api\V1\PagoController;
use App\Http\Controllers\Api\V1\PedidoController;
use App\Http\Controllers\Api\V1\PerfilController;
use App\Http\Controllers\Api\V1\RepartidorController;
use App\Http\Controllers\Api\V1\ZonaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1  (prefijo /api/v1)
|--------------------------------------------------------------------------
| El rol se valida SIEMPRE en el servidor (middleware `rol` y Policies).
| Los tokens llevan la habilidad de su app: `cliente` o `negocio`.
*/

// ---------- Público ----------
Route::get('app/version', [DispositivoController::class, 'version']);
Route::get('categorias', [CatalogoController::class, 'categorias']);
Route::get('productos', [CatalogoController::class, 'productos']);
Route::get('productos/{id}', [CatalogoController::class, 'show'])->whereNumber('id');
Route::get('configuracion-pago', [PagoController::class, 'configuracion']);
Route::post('zonas/validar', [ZonaController::class, 'validar']);

Route::prefix('auth')->group(function () {
    Route::post('registro', [AuthController::class, 'registro'])->middleware('throttle:login');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('recuperar', [AuthController::class, 'recuperar'])->middleware('throttle:login');
    Route::post('restablecer', [AuthController::class, 'restablecer'])->middleware('throttle:login');
});

// Archivos públicos (imágenes de productos y QR) y privados con URL firmada y temporal
Route::get('storage/{path}', [ArchivoPrivadoController::class, 'publico'])->where('path', '.*');
Route::get('comprobantes/{pago}', [ArchivoPrivadoController::class, 'comprobante'])
    ->name('comprobantes.ver')->middleware('signed:relative');
Route::get('entregas/{pedido}/foto', [ArchivoPrivadoController::class, 'fotoEntrega'])
    ->name('entregas.foto')->middleware('signed:relative');

// ---------- Autenticado ----------
Route::middleware(['auth:sanctum', 'activo', 'throttle:api'])->group(function () {

    // Comunes a ambas apps
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('perfil', [PerfilController::class, 'show']);
    Route::put('perfil', [PerfilController::class, 'update']);
    Route::delete('perfil', [PerfilController::class, 'destroy']);
    Route::post('dispositivos', [DispositivoController::class, 'store']);

    Route::get('notificaciones', [NotificacionController::class, 'index']);
    Route::post('notificaciones/leidas', [NotificacionController::class, 'marcarTodas']);
    Route::put('notificaciones/{id}/leida', [NotificacionController::class, 'marcarLeida'])->whereNumber('id');

    // ---------- App Cliente ----------
    Route::middleware(['ability:cliente'])->group(function () {
        Route::apiResource('direcciones', DireccionController::class)
            ->parameters(['direcciones' => 'direccion']);

        Route::get('perfil/gastos', [PerfilController::class, 'gastos']);
        Route::get('favoritos', [CatalogoController::class, 'favoritos']);
        Route::post('favoritos/{id}', [CatalogoController::class, 'toggleFavorito'])->whereNumber('id');

        Route::get('pedidos', [PedidoController::class, 'index']);
        Route::post('pedidos', [PedidoController::class, 'store'])->middleware('throttle:pedidos');
        Route::get('pedidos/{pedido}', [PedidoController::class, 'show']);
        Route::get('pedidos/{pedido}/comprobante-pdf', [PedidoController::class, 'comprobantePdf']);
        Route::post('pedidos/{pedido}/calificar', [PedidoController::class, 'calificar']);
        Route::post('pedidos/{pedido}/cancelar', [PedidoController::class, 'cancelar']);
        Route::post('pedidos/{pedido}/pago/comprobante', [PagoController::class, 'comprobante'])
            ->middleware('throttle:uploads');
    });

    // ---------- App Negocio: repartidor ----------
    Route::prefix('repartidor')->middleware(['ability:negocio', 'rol:repartidor'])->group(function () {
        Route::get('pedidos', [RepartidorController::class, 'pedidos']);
        Route::post('pedidos/{pedido}/en-camino', [RepartidorController::class, 'enCamino']);
        Route::post('pedidos/{pedido}/ubicacion', [RepartidorController::class, 'ubicacion']);
        Route::post('pedidos/{pedido}/entregar', [RepartidorController::class, 'entregar'])
            ->middleware(['throttle:entrega', 'throttle:uploads']);
        Route::post('pedidos/{pedido}/fallida', [RepartidorController::class, 'fallida']);
    });

    // ---------- App Negocio: operador y admin ----------
    Route::prefix('admin')->middleware(['ability:negocio'])->group(function () {

        Route::middleware('rol:admin,operador')->group(function () {
            Route::get('dashboard', Admin\DashboardController::class);

            Route::get('pagos', [Admin\PagoAdminController::class, 'index']);
            Route::get('pagos/{pago}', [Admin\PagoAdminController::class, 'show']);
            Route::post('pagos/{pago}/aprobar', [Admin\PagoAdminController::class, 'aprobar']);
            Route::post('pagos/{pago}/rechazar', [Admin\PagoAdminController::class, 'rechazar']);

            Route::get('pedidos', [Admin\PedidoAdminController::class, 'index']);
            Route::get('pedidos/{pedido}', [Admin\PedidoAdminController::class, 'show']);
            Route::put('pedidos/{pedido}/estado', [Admin\PedidoAdminController::class, 'cambiarEstado']);
            Route::put('pedidos/{pedido}/repartidor', [Admin\PedidoAdminController::class, 'asignarRepartidor']);
            Route::get('repartidores', [Admin\PedidoAdminController::class, 'repartidores']);
        });

        // Solo admin
        Route::middleware('rol:admin')->group(function () {
            Route::apiResource('productos', Admin\ProductoAdminController::class)->parameters(['productos' => 'producto']);
            Route::post('productos/{producto}', [Admin\ProductoAdminController::class, 'update'])
                ->middleware('throttle:uploads');
            Route::patch('productos/{producto}/stock', [Admin\ProductoAdminController::class, 'stock']);
            Route::post('productos/{producto}/imagenes', [Admin\ProductoAdminController::class, 'agregarImagenes'])
                ->middleware('throttle:uploads');
            Route::delete('producto-imagenes/{imagen}', [Admin\ProductoAdminController::class, 'quitarImagen'])->whereNumber('imagen');

            Route::apiResource('categorias', Admin\CategoriaAdminController::class)->parameters(['categorias' => 'categoria']);
            Route::apiResource('zonas', Admin\ZonaAdminController::class)->parameters(['zonas' => 'zona']);

            Route::apiResource('usuarios', Admin\UsuarioAdminController::class)->except('destroy')->parameters(['usuarios' => 'usuario']);
            Route::get('usuarios/{usuario}/sesiones', [Admin\UsuarioAdminController::class, 'sesiones']);
            Route::delete('usuarios/{usuario}/sesiones', [Admin\UsuarioAdminController::class, 'revocarSesiones']);
            Route::delete('usuarios/{usuario}/sesiones/{token}', [Admin\UsuarioAdminController::class, 'revocarSesion'])->whereNumber('token');

            Route::get('configuracion-pago', [Admin\ConfiguracionPagoAdminController::class, 'show']);
            Route::match(['put', 'post'], 'configuracion-pago', [Admin\ConfiguracionPagoAdminController::class, 'update'])
                ->middleware('throttle:uploads');

            Route::get('reportes/ventas', [Admin\ReporteController::class, 'ventas']);
            Route::get('auditoria', [Admin\AuditoriaController::class, 'index']);
            Route::put('app-versiones/{app}', [Admin\AuditoriaController::class, 'versionMinima']);
        });
    });
});
