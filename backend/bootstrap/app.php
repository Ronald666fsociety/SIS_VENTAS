<?php

use App\Http\Middleware\ForzarJson;
use App\Http\Middleware\RolMiddleware;
use App\Http\Middleware\UsuarioActivo;
use App\Http\Middleware\VersionMinima;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [ForzarJson::class]);
        $middleware->api(append: [VersionMinima::class]);

        $middleware->alias([
            'rol' => RolMiddleware::class,
            'activo' => UsuarioActivo::class,
            'abilities' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
            'ability' => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return response()->json(['message' => 'Sesión no válida o vencida. Inicia sesión de nuevo.'], 401);
        });
        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'No encontrado.'], 404);
            }
        });
    })->create();
