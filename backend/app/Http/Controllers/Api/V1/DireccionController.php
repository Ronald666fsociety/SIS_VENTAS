<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Direccion;
use App\Services\ZonaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DireccionController extends Controller
{
    public function __construct(private ZonaService $zonas)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $direcciones = $request->user()->direcciones()->with('zona')
            ->orderByDesc('es_principal')->orderByDesc('id')->get();

        return response()->json(['data' => $direcciones]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validar($request);
        $user = $request->user();

        $direccion = DB::transaction(function () use ($data, $user) {
            $data['zona_id'] = $this->zonas->resolver($data['lat'], $data['lng'])?->id;
            $esPrimera = ! $user->direcciones()->exists();
            $data['es_principal'] = $esPrimera || ($data['es_principal'] ?? false);

            if ($data['es_principal']) {
                $user->direcciones()->update(['es_principal' => false]);
            }

            return $user->direcciones()->create($data);
        });

        return response()->json($direccion->load('zona'), 201);
    }

    public function show(Direccion $direccion): JsonResponse
    {
        $this->authorize('view', $direccion);

        return response()->json($direccion->load('zona'));
    }

    public function update(Request $request, Direccion $direccion): JsonResponse
    {
        $this->authorize('update', $direccion);
        $data = $this->validar($request, parcial: true);

        DB::transaction(function () use ($data, $direccion, $request) {
            if (isset($data['lat']) || isset($data['lng'])) {
                $data['zona_id'] = $this->zonas->resolver($data['lat'] ?? $direccion->lat, $data['lng'] ?? $direccion->lng)?->id;
            }
            if (! empty($data['es_principal'])) {
                $request->user()->direcciones()->where('id', '!=', $direccion->id)->update(['es_principal' => false]);
            }
            $direccion->update($data);
        });

        return response()->json($direccion->fresh('zona'));
    }

    public function destroy(Direccion $direccion): JsonResponse
    {
        $this->authorize('delete', $direccion);
        $direccion->delete(); // los pedidos conservan su copia de la dirección

        return response()->json(['message' => 'Dirección eliminada.']);
    }

    private function validar(Request $request, bool $parcial = false): array
    {
        $req = $parcial ? 'sometimes' : 'required';

        return $request->validate([
            'alias' => [$req, 'string', 'max:50'],
            'direccion' => [$req, 'string', 'max:500'],
            'referencia' => ['nullable', 'string', 'max:300'],
            'lat' => [$req, 'numeric', 'between:-90,90'],
            'lng' => [$req, 'numeric', 'between:-180,180'],
            'es_principal' => ['sometimes', 'boolean'],
        ]);
    }
}
