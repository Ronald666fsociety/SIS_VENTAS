<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductoResource;
use App\Models\Producto;
use App\Models\ProductoImagen;
use App\Services\AuditoriaService;
use App\Services\ImagenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductoAdminController extends Controller
{
    public function __construct(private ImagenService $imagenes, private AuditoriaService $auditoria)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $q = Producto::query()->with(['imagenes', 'categoria']);

        $q->when($request->filled('q'), function ($x) use ($request) {
            $t = '%'.mb_strtolower(str_replace(['%', '_'], ['\%', '\_'], $request->input('q'))).'%';
            $x->where(fn ($w) => $w->whereRaw('LOWER(nombre) LIKE ?', [$t])->orWhereRaw('LOWER(sku) LIKE ?', [$t]));
        });
        $q->when($request->filled('categoria'), fn ($x) => $x->where('categoria_id', $request->integer('categoria')));
        $q->when($request->filled('activo'), fn ($x) => $x->where('activo', $request->boolean('activo')));
        $q->when($request->boolean('stock_bajo'), fn ($x) => $x->whereColumn('stock', '<=', 'stock_minimo'));

        return ProductoResource::collection($q->orderBy('nombre')->paginate(50)->withQueryString());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validar($request);
        $request->validate($this->reglasImagenes());
        $archivos = $this->extraerArchivosImagenes($request);

        $producto = DB::transaction(function () use ($data, $archivos) {
            $p = Producto::create($data);
            $this->guardarImagenes($p, $archivos);

            return $p;
        });
        $this->auditoria->registrar($request->user(), 'producto.creado', 'producto', $producto->id, ['nombre' => $producto->nombre]);

        return (new ProductoResource($producto->load(['imagenes', 'categoria'])))->response()->setStatusCode(201);
    }

    public function show(Producto $producto): ProductoResource
    {
        return new ProductoResource($producto->load(['imagenes', 'categoria']));
    }

    public function update(Request $request, Producto $producto): ProductoResource
    {
        $data = $this->validar($request, $producto);
        $request->validate($this->reglasImagenes());
        $archivos = $this->extraerArchivosImagenes($request);
        $antes = $producto->only(['precio', 'precio_oferta', 'stock', 'activo']);

        DB::transaction(function () use ($producto, $data, $archivos) {
            $producto->update($data);
            if (! empty($archivos)) {
                $this->guardarImagenes($producto, $archivos);
            }
        });

        $cambios = array_filter([
            'precio' => $this->cambio($antes['precio'], $producto->precio),
            'precio_oferta' => $this->cambio($antes['precio_oferta'], $producto->precio_oferta),
            'stock' => $this->cambio($antes['stock'], $producto->stock),
            'activo' => $this->cambio($antes['activo'], $producto->activo),
        ]);
        if ($cambios) {
            $accion = isset($cambios['precio']) || isset($cambios['precio_oferta']) ? 'producto.precio_editado' : 'producto.editado';
            $this->auditoria->registrar($request->user(), $accion, 'producto', $producto->id, $cambios);
        }

        return new ProductoResource($producto->fresh(['imagenes', 'categoria']));
    }

    /** Edición rápida de stock con botones + / −: {delta: 5} o {stock: 20}. */
    public function stock(Request $request, Producto $producto): ProductoResource
    {
        $data = $request->validate([
            'delta' => ['required_without:stock', 'integer', 'between:-100000,100000'],
            'stock' => ['required_without:delta', 'integer', 'min:0', 'max:1000000'],
        ]);

        DB::transaction(function () use ($data, $producto, $request) {
            $p = Producto::query()->lockForUpdate()->findOrFail($producto->id);
            $nuevo = isset($data['stock']) ? (int) $data['stock'] : $p->stock + (int) $data['delta'];
            if ($nuevo < 0) {
                abort(422, 'El stock no puede ser negativo.');
            }
            $antes = $p->stock;
            $p->update(['stock' => $nuevo]);
            $this->auditoria->registrar($request->user(), 'producto.stock_editado', 'producto', $p->id, ['de' => $antes, 'a' => $nuevo]);
        });

        return new ProductoResource($producto->fresh(['imagenes', 'categoria']));
    }

    public function destroy(Request $request, Producto $producto): JsonResponse
    {
        $producto->delete(); // eliminación lógica: los pedidos conservan su copia histórica
        $this->auditoria->registrar($request->user(), 'producto.eliminado', 'producto', $producto->id, ['nombre' => $producto->nombre]);

        return response()->json(['message' => 'Producto eliminado.']);
    }

    public function agregarImagenes(Request $request, Producto $producto): ProductoResource
    {
        $request->validate($this->reglasImagenes());
        $archivos = $this->extraerArchivosImagenes($request);
        if (empty($archivos)) {
            abort(422, 'Debes adjuntar al menos una imagen.');
        }
        $this->guardarImagenes($producto, $archivos);

        return new ProductoResource($producto->fresh(['imagenes', 'categoria']));
    }

    public function quitarImagen(int $imagenId): JsonResponse
    {
        $img = ProductoImagen::findOrFail($imagenId);
        $this->borrarArchivo($img->url);
        $img->delete();

        return response()->json(['message' => 'Imagen eliminada.']);
    }

    private function validar(Request $request, ?Producto $producto = null): array
    {
        $req = $producto ? 'sometimes' : 'required';

        if ($request->has('precio_oferta') && ($request->input('precio_oferta') === '' || $request->input('precio_oferta') === 'null' || $request->input('precio_oferta') === 'NaN' || (float) $request->input('precio_oferta') <= 0)) {
            $request->merge(['precio_oferta' => null]);
        }
        if ($request->has('sku') && trim((string) $request->input('sku')) === '') {
            $request->merge(['sku' => null]);
        }

        // En actualizaciones parciales, `lt:precio` necesita el precio vigente para comparar.
        if ($producto && $request->filled('precio_oferta') && ! $request->has('precio')) {
            $request->merge(['precio' => $producto->precio]);
        }

        return $request->validate([
            'categoria_id' => [$req, 'integer', 'exists:categorias,id'],
            'nombre' => [$req, 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'sku' => ['nullable', 'string', 'max:50', Rule::unique('productos', 'sku')->ignore($producto?->id)],
            'precio' => [$req, 'numeric', 'min:0', 'max:99999999'],
            'precio_oferta' => ['nullable', 'numeric', 'min:0', 'lt:precio'],
            'unidad' => ['sometimes', 'string', 'max:20'],
            'stock' => ['sometimes', 'integer', 'min:0'],
            'stock_minimo' => ['sometimes', 'integer', 'min:0'],
            'activo' => ['sometimes', 'boolean'],
            'destacado' => ['sometimes', 'boolean'],
        ]);
    }

    private function reglasImagenes(): array
    {
        return [
            'imagenes' => ['sometimes'],
            'imagenes.*' => ['file', 'max:8192'],
            'imagen' => ['sometimes', 'nullable', 'file', 'max:8192'],
        ];
    }

    private function extraerArchivosImagenes(Request $request): array
    {
        $archivos = [];
        if ($request->hasFile('imagenes')) {
            $f = $request->file('imagenes');
            $archivos = is_array($f) ? $f : [$f];
        }
        if ($request->hasFile('imagen')) {
            $f = $request->file('imagen');
            $archivos = array_merge($archivos, is_array($f) ? $f : [$f]);
        }

        return array_values(array_filter($archivos, fn ($a) => $a instanceof UploadedFile));
    }

    private function guardarImagenes(Producto $producto, array $archivos): void
    {
        $disco = config('tienda.disco_publico');
        $orden = (int) $producto->imagenes()->max('orden');

        foreach ($archivos as $archivo) {
            if (! $archivo instanceof UploadedFile) {
                continue;
            }
            $ruta = $this->imagenes->guardar($archivo, $disco, 'productos/'.$producto->id, 1200, 'webp');
            $url = '/api/v1/storage/'.ltrim($ruta, '/');
            $producto->imagenes()->create(['url' => $url, 'orden' => ++$orden]);
        }
    }

    private function borrarArchivo(string $url): void
    {
        $disco = config('tienda.disco_publico');
        if (preg_match('#(?:/api/v1)?/storage/(.+)$#', $url, $m)) {
            Storage::disk($disco)->delete($m[1]);
        }
    }

    private function cambio(mixed $antes, mixed $despues): ?array
    {
        return (string) $antes === (string) $despues ? null : ['de' => $antes, 'a' => $despues];
    }
}
