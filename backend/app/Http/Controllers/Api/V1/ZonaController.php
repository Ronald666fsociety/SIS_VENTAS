<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ZonaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ZonaController extends Controller
{
    public function validar(Request $request, ZonaService $zonas): JsonResponse
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $zona = $zonas->resolver((float) $data['lat'], (float) $data['lng']);

        if (! $zona) {
            return response()->json([
                'cubre' => false,
                'zona' => null,
                'mensaje' => 'Aún no llegamos a esta zona.',
            ]);
        }

        return response()->json([
            'cubre' => true,
            'zona' => ['id' => $zona->id, 'nombre' => $zona->nombre],
            'costo_envio' => $zona->costo_envio,
            'pedido_minimo' => $zona->pedido_minimo,
            'mensaje' => 'Entregamos en tu zona · Envío Bs '.number_format((float) $zona->costo_envio, 2),
        ]);
    }
}
