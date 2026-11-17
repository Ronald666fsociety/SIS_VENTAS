<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Bloquea cuentas desactivadas y revoca su token actual. */
class UsuarioActivo
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && ! $user->activo) {
            $user->currentAccessToken()?->delete();

            return response()->json(['message' => 'Tu cuenta está desactivada. Contacta al negocio.'], 403);
        }

        return $next($request);
    }
}
