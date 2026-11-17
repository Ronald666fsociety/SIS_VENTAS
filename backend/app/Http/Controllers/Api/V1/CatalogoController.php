<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductoResource;
use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class CatalogoController extends Controller
{
    public function categorias(): JsonResponse
    {
        $categorias = Cache::remember('catalogo.categorias', 300, fn () => Categoria::query()
            ->where('activa', true)->orderBy('orden')->orderBy('nombre')->get()->toArray());

        return response()->json(['data' => $categorias]);
    }

    public function productos(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'categoria' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:100'],
            'destacado' => ['nullable', 'boolean'],
            'oferta' => ['nullable', 'boolean'],
        ]);

        $q = Producto::query()->visibles()->with('imagenes');

        if ($request->filled('categoria')) {
            $q->where('categoria_id', $request->integer('categoria'));
        }
        if ($request->filled('q')) {
            $termino = '%'.mb_strtolower(str_replace(['%', '_'], ['\%', '\_'], $request->input('q'))).'%';
            $q->whereRaw('LOWER(nombre) LIKE ?', [$termino]);
        }
        if ($request->boolean('destacado')) {
            $q->where('destacado', true);
        }
        if ($request->boolean('oferta')) {
            $q->whereNotNull('precio_oferta')->whereColumn('precio_oferta', '<', 'precio');
        }

        return ProductoResource::collection($q->orderBy('nombre')->paginate(20)->withQueryString());
    }

    public function show(int $id): ProductoResource
    {
        $producto = Producto::query()->visibles()->with(['imagenes', 'categoria'])->findOrFail($id);

        return new ProductoResource($producto);
    }

    public function favoritos(Request $request): JsonResponse
    {
        $productos = $request->user()->favoritos()->visibles()->with(['imagenes', 'categoria'])->orderBy('nombre')->get();

        return response()->json([
            'ids' => $productos->pluck('id')->values(),
            'data' => ProductoResource::collection($productos),
        ]);
    }

    public function toggleFavorito(Request $request, int $id): JsonResponse
    {
        $producto = Producto::query()->visibles()->findOrFail($id);
        $user = $request->user();

        $yaEs = $user->favoritos()->where('producto_id', $producto->id)->exists();
        if ($yaEs) {
            $user->favoritos()->detach($producto->id);
        } else {
            $user->favoritos()->attach($producto->id);
        }

        return response()->json([
            'favorito' => ! $yaEs,
            'ids' => $user->favoritos()->pluck('productos.id')->values(),
        ]);
    }
}
