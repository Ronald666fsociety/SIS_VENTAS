<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use App\Models\Dispositivo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DispositivoController extends Controller
{
    /** Registra/actualiza el token FCM. La app se deduce del rol: personal = negocio, cliente = cliente. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:4096'],
            'plataforma' => ['required', 'in:android,ios'],
            'version_app' => ['nullable', 'string', 'max:20'],
        ]);
        $user = $request->user();

        Dispositivo::updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id' => $user->id,
                'plataforma' => $data['plataforma'],
                'app' => $user->esPersonal() ? 'negocio' : 'cliente',
                'version_app' => $data['version_app'] ?? $request->header('X-App-Version'),
                'ultimo_uso' => now(),
            ],
        );

        return response()->json(['message' => 'Dispositivo registrado.'], 201);
    }

    /** Público: la app consulta al abrir si su versión sigue permitida. */
    public function version(Request $request): JsonResponse
    {
        $data = $request->validate([
            'app' => ['required', 'in:cliente,negocio'],
            'version' => ['required', 'string', 'max:20'],
        ]);

        $minima = AppVersion::find($data['app']);
        $requiere = $minima && version_compare($data['version'], $minima->version_minima, '<');

        return response()->json([
            'requiere_actualizacion' => $requiere,
            'version_minima' => $minima?->version_minima,
            'url_tienda' => $minima?->url_tienda,
        ]);
    }
}
