<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ZonaEnvio;
use App\Services\AuditoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ZonaAdminController extends Controller
{
    public function __construct(private AuditoriaService $auditoria)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json(['data' => ZonaEnvio::query()->orderBy('nombre')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validar($request);
        $this->exigirCobertura($data);
        $zona = ZonaEnvio::create($data);
        $this->auditoria->registrar($request->user(), 'zona.creada', 'zona', $zona->id, ['nombre' => $zona->nombre, 'costo_envio' => $zona->costo_envio]);

        return response()->json($zona, 201);
    }

    public function show(ZonaEnvio $zona): JsonResponse
    {
        return response()->json($zona);
    }

    public function update(Request $request, ZonaEnvio $zona): JsonResponse
    {
        $data = $this->validar($request, true);
        $this->exigirCobertura($data + $zona->only(['centro_lat', 'centro_lng', 'radio_km', 'poligono']));
        $anterior = $zona->costo_envio;
        $zona->update($data);
        if ((string) $anterior !== (string) $zona->costo_envio) {
            $this->auditoria->registrar($request->user(), 'zona.costo_editado', 'zona', $zona->id, ['de' => $anterior, 'a' => $zona->costo_envio]);
        }

        return response()->json($zona->fresh());
    }

    public function destroy(Request $request, ZonaEnvio $zona): JsonResponse
    {
        // Las direcciones/pedidos conservan referencia: se desactiva en vez de borrar si ya se usó.
        if ($zona->id && (\App\Models\Pedido::where('zona_id', $zona->id)->exists() || \App\Models\Direccion::where('zona_id', $zona->id)->exists())) {
            $zona->update(['activa' => false]);
            $mensaje = 'La zona tiene historial; se desactivó.';
        } else {
            $zona->delete();
            $mensaje = 'Zona eliminada.';
        }
        $this->auditoria->registrar($request->user(), 'zona.eliminada', 'zona', $zona->id);

        return response()->json(['message' => $mensaje]);
    }

    private function validar(Request $request, bool $parcial = false): array
    {
        $req = $parcial ? 'sometimes' : 'required';

        return $request->validate([
            'nombre' => [$req, 'string', 'max:100'],
            'costo_envio' => [$req, 'numeric', 'min:0', 'max:99999999'],
            'pedido_minimo' => ['sometimes', 'numeric', 'min:0', 'max:99999999'],
            'centro_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'centro_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'radio_km' => ['nullable', 'numeric', 'gt:0', 'max:5000'],
            'poligono' => ['nullable', 'array'],
            'activa' => ['sometimes', 'boolean'],
        ]);
    }

    private function exigirCobertura(array $d): void
    {
        $tieneRadio = isset($d['centro_lat'], $d['centro_lng'], $d['radio_km']);
        if (! $tieneRadio && empty($d['poligono'])) {
            throw ValidationException::withMessages(['radio_km' => 'Define centro y radio, o un polígono.']);
        }
    }
}
