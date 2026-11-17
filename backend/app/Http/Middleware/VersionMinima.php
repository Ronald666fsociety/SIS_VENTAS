<?php

namespace App\Http\Middleware;

use App\Models\AppVersion;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Bloquea versiones viejas de las apps. Las apps envían:
 *   X-App: cliente | negocio
 *   X-App-Version: 1.2.3
 * Si faltan las cabeceras (herramientas, pruebas) no se bloquea.
 */
class VersionMinima
{
    public function handle(Request $request, Closure $next)
    {
        $app = $request->header('X-App');
        $version = $request->header('X-App-Version');

        if ($app && $version) {
            $minima = Cache::remember("app_version.$app", 60, fn () => AppVersion::find($app)?->only(['version_minima', 'url_tienda']) ?? []);

            if (! empty($minima['version_minima']) && version_compare($version, $minima['version_minima'], '<')) {
                return response()->json([
                    'message' => 'Hay una nueva versión de la app. Actualiza para continuar.',
                    'actualizar' => true,
                    'version_minima' => $minima['version_minima'],
                    'url_tienda' => $minima['url_tienda'] ?? null,
                ], 426);
            }
        }

        return $next($request);
    }
}
