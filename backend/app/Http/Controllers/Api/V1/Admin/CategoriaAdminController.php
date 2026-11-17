<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Services\AuditoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CategoriaAdminController extends Controller
{
    public function __construct(private AuditoriaService $auditoria)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json(['data' => Categoria::query()->withCount('productos')->orderBy('orden')->orderBy('nombre')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validar($request);
        $data['slug'] = $this->slugUnico($data['nombre']);
        $categoria = Categoria::create($data);
        Cache::forget('catalogo.categorias');
        $this->auditoria->registrar($request->user(), 'categoria.creada', 'categoria', $categoria->id);

        return response()->json($categoria, 201);
    }

    public function show(Categoria $categoria): JsonResponse
    {
        return response()->json($categoria);
    }

    public function update(Request $request, Categoria $categoria): JsonResponse
    {
        $data = $this->validar($request, true);
        if (isset($data['nombre']) && $data['nombre'] !== $categoria->nombre) {
            $data['slug'] = $this->slugUnico($data['nombre'], $categoria->id);
        }
        $categoria->update($data);
        Cache::forget('catalogo.categorias');

        return response()->json($categoria->fresh());
    }

    public function destroy(Request $request, Categoria $categoria): JsonResponse
    {
        if ($categoria->productos()->exists()) {
            throw ValidationException::withMessages(['categoria' => 'La categoría tiene productos. Muévelos o desactívala.']);
        }
        $categoria->delete();
        Cache::forget('catalogo.categorias');
        $this->auditoria->registrar($request->user(), 'categoria.eliminada', 'categoria', $categoria->id);

        return response()->json(['message' => 'Categoría eliminada.']);
    }

    private function validar(Request $request, bool $parcial = false): array
    {
        $req = $parcial ? 'sometimes' : 'required';

        return $request->validate([
            'nombre' => [$req, 'string', 'max:100'],
            'imagen_url' => ['nullable', 'string', 'max:500'],
            'orden' => ['sometimes', 'integer'],
            'activa' => ['sometimes', 'boolean'],
        ]);
    }

    private function slugUnico(string $nombre, ?int $ignorarId = null): string
    {
        $base = Str::slug($nombre) ?: 'categoria';
        $slug = $base;
        $i = 2;
        while (Categoria::where('slug', $slug)->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
