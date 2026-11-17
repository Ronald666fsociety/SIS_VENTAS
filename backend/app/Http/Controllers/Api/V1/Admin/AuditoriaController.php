<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use App\Models\Auditoria;
use App\Services\AuditoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AuditoriaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['nullable', 'integer'],
            'accion' => ['nullable', 'string', 'max:60'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        $q = Auditoria::query()->with('usuario:id,nombre,apellido,rol');
        $q->when($request->filled('user_id'), fn ($x) => $x->where('user_id', $request->integer('user_id')));
        $q->when($request->filled('accion'), fn ($x) => $x->where('accion', 'like', $request->input('accion').'%'));
        $q->when($request->filled('desde'), fn ($x) => $x->where('created_at', '>=', $request->date('desde')->startOfDay()));
        $q->when($request->filled('hasta'), fn ($x) => $x->where('created_at', '<=', $request->date('hasta')->endOfDay()));

        return response()->json($q->orderByDesc('id')->paginate(50)->withQueryString());
    }

    /** Versión mínima permitida por app (fuerza actualización). */
    public function versionMinima(Request $request, string $app, AuditoriaService $auditoria): JsonResponse
    {
        abort_unless(in_array($app, ['cliente', 'negocio'], true), 404);
        $data = $request->validate([
            'version_minima' => ['required', 'string', 'regex:/^\d+(\.\d+){0,3}$/'],
            'url_tienda' => ['nullable', 'url', 'max:500'],
        ]);

        $v = AppVersion::updateOrCreate(['app' => $app], $data);
        Cache::forget("app_version.$app");
        $auditoria->registrar($request->user(), 'app.version_minima', 'app', null, ['app' => $app] + $data);

        return response()->json($v);
    }
}
