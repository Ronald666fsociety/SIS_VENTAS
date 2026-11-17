<?php

namespace App\Http\Middleware;

use App\Enums\Rol;
use Closure;
use Illuminate\Http\Request;

/** Autoriza por rol en el servidor: rol:admin,operador */
class RolMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();
        $permitidos = array_map(fn (string $r) => Rol::from($r), $roles);

        if (! $user || ! $user->tieneRol(...$permitidos)) {
            return response()->json(['message' => 'No tienes permiso para esta acción.'], 403);
        }

        return $next($request);
    }
}
