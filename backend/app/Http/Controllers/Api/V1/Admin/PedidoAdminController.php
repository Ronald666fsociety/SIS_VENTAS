<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\EstadoPedido;
use App\Http\Controllers\Controller;
use App\Http\Resources\PedidoResource;
use App\Models\Pedido;
use App\Services\AuditoriaService;
use App\Services\PedidoService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class PedidoAdminController extends Controller
{
    public function __construct(private PedidoService $pedidos, private AuditoriaService $auditoria)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'estado' => ['nullable', Rule::enum(EstadoPedido::class)],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
            'zona_id' => ['nullable', 'integer'],
            'repartidor_id' => ['nullable', 'integer'],
            'hoy' => ['nullable', 'boolean'],
            'q' => ['nullable', 'string', 'max:50'],
        ]);

        $q = Pedido::query()->with(['cliente', 'zona', 'repartidor', 'pago']);

        $q->when($request->filled('estado'), fn ($x) => $x->where('estado', $request->input('estado')));
        $q->when($request->filled('zona_id'), fn ($x) => $x->where('zona_id', $request->integer('zona_id')));
        $q->when($request->filled('repartidor_id'), fn ($x) => $x->where('repartidor_id', $request->integer('repartidor_id')));
        $q->when($request->filled('desde'), fn ($x) => $x->where('created_at', '>=', $request->date('desde')->startOfDay()));
        $q->when($request->filled('hasta'), fn ($x) => $x->where('created_at', '<=', $request->date('hasta')->endOfDay()));
        $q->when($request->boolean('hoy'), fn ($x) => $x->where('created_at', '>=', now()->startOfDay()));
        $q->when($request->filled('q'), function ($x) use ($request) {
            $t = str_replace(['%', '_'], ['\%', '\_'], $request->input('q'));
            $x->where(fn ($w) => $w
                ->where('codigo', 'like', "%{$t}%")
                ->orWhereHas('cliente', fn ($c) => $c->where('telefono', 'like', "%{$t}%")->orWhere('nombre', 'like', "%{$t}%")));
        });

        return PedidoResource::collection($q->orderByDesc('created_at')->orderByDesc('id')->paginate(20)->withQueryString());
    }

    public function repartidores(): \Illuminate\Http\JsonResponse
    {
        $repartidores = \App\Models\User::query()
            ->where('rol', \App\Enums\Rol::Repartidor->value)->where('activo', true)
            ->orderBy('nombre')->get(['id', 'nombre', 'apellido', 'telefono']);

        return response()->json(['data' => $repartidores]);
    }

    public function show(Pedido $pedido): PedidoResource
    {
        return new PedidoResource($pedido->load(['cliente', 'zona', 'repartidor', 'pago', 'items', 'estados']));
    }

    public function cambiarEstado(Request $request, Pedido $pedido): PedidoResource
    {
        $data = $request->validate([
            'estado' => ['required', Rule::enum(EstadoPedido::class)],
            'nota' => ['nullable', 'string', 'max:300'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);
        $anterior = $pedido->estado->value;
        $extra = [];
        if ($data['estado'] === EstadoPedido::EnCamino->value && isset($data['lat'], $data['lng'])) {
            $extra = [
                'repartidor_lat' => (float) $data['lat'],
                'repartidor_lng' => (float) $data['lng'],
                'repartidor_ubicacion_at' => now(),
            ];
        }

        $this->pedidos->cambiarEstado($pedido, EstadoPedido::from($data['estado']), $request->user(), $data['nota'] ?? null, extra: $extra);
        $this->auditoria->registrar($request->user(), 'pedido.estado', 'pedido', $pedido->id, [
            'de' => $anterior,
            'a' => $data['estado'],
        ]);

        return new PedidoResource($pedido->fresh(['cliente', 'zona', 'repartidor', 'pago', 'items', 'estados']));
    }

    public function asignarRepartidor(Request $request, Pedido $pedido): PedidoResource
    {
        $data = $request->validate(['repartidor_id' => ['required', 'integer']]);
        $this->pedidos->asignarRepartidor($pedido, $data['repartidor_id'], $request->user());

        return new PedidoResource($pedido->fresh(['cliente', 'zona', 'repartidor', 'pago', 'items', 'estados']));
    }
}
