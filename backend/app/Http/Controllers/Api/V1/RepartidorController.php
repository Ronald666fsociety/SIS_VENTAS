<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EstadoPedido;
use App\Http\Controllers\Controller;
use App\Http\Resources\PedidoResource;
use App\Models\Pedido;
use App\Services\PedidoService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RepartidorController extends Controller
{
    public function __construct(private PedidoService $pedidos)
    {
    }

    /** Pedidos asignados. ?historial=1 devuelve los ya cerrados (entregados / fallidos). */
    public function pedidos(Request $request): AnonymousResourceCollection
    {
        $estados = $request->boolean('historial')
            ? [EstadoPedido::Entregado, EstadoPedido::EntregaFallida]
            : [EstadoPedido::ListoParaEnvio, EstadoPedido::EnCamino];

        $pedidos = Pedido::query()
            ->where('repartidor_id', $request->user()->id)
            ->whereIn('estado', array_map(fn ($e) => $e->value, $estados))
            ->with(['items', 'cliente', 'pago'])
            ->orderByDesc('updated_at')
            ->paginate(20);

        return PedidoResource::collection($pedidos);
    }

    public function enCamino(Request $request, Pedido $pedido): PedidoResource
    {
        $this->authorize('gestionarEntrega', $pedido);
        $data = $request->validate([
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);
        $lat = isset($data['lat']) ? (float) $data['lat'] : null;
        $lng = isset($data['lng']) ? (float) $data['lng'] : null;

        $this->pedidos->marcarEnCamino($pedido, $request->user(), $lat, $lng);

        return new PedidoResource($pedido->fresh(['items', 'cliente', 'estados']));
    }

    public function ubicacion(Request $request, Pedido $pedido): PedidoResource
    {
        $this->authorize('gestionarEntrega', $pedido);
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $this->pedidos->actualizarUbicacionRepartidor($pedido, (float) $data['lat'], (float) $data['lng']);

        return new PedidoResource($pedido->fresh(['items', 'cliente', 'estados']));
    }

    public function entregar(Request $request, Pedido $pedido): PedidoResource
    {
        $this->authorize('gestionarEntrega', $pedido);
        $data = $request->validate([
            'codigo' => ['required', 'digits:4'],
            'foto' => ['required', 'file', 'max:8192'],
        ]);

        $this->pedidos->entregar($pedido, $request->user(), $data['codigo'], $request->file('foto'));

        return new PedidoResource($pedido->fresh(['items', 'cliente', 'estados']));
    }

    public function fallida(Request $request, Pedido $pedido): PedidoResource
    {
        $this->authorize('gestionarEntrega', $pedido);
        $data = $request->validate(['nota' => ['required', 'string', 'max:300']]);

        $this->pedidos->cambiarEstado($pedido, EstadoPedido::EntregaFallida, $request->user(), $data['nota']);

        return new PedidoResource($pedido->fresh(['items', 'cliente', 'estados']));
    }
}
