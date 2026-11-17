<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EstadoPedido;
use App\Http\Controllers\Controller;
use App\Http\Requests\CrearPedidoRequest;
use App\Http\Resources\PedidoResource;
use App\Models\Pedido;
use App\Services\PedidoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class PedidoController extends Controller
{
    public function __construct(private PedidoService $pedidos)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $pedidos = $request->user()->pedidos()
            ->with(['items', 'pago'])
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(15);

        return PedidoResource::collection($pedidos);
    }

    public function store(CrearPedidoRequest $request): PedidoResource
    {
        $pedido = $this->pedidos->crear($request->user(), $request->validated());

        return (new PedidoResource($pedido))->additional(['verificacion_minutos' => config('tienda.verificacion_minutos')]);
    }

    public function show(Pedido $pedido): PedidoResource
    {
        $this->authorize('view', $pedido);

        return new PedidoResource($pedido->load(['items', 'pago', 'estados', 'zona', 'repartidor']));
    }

    public function cancelar(Request $request, Pedido $pedido): PedidoResource
    {
        $this->authorize('cancelar', $pedido);
        $this->pedidos->cancelarPorCliente($pedido, $request->user());

        return new PedidoResource($pedido->fresh(['items', 'pago', 'estados']));
    }

    public function calificar(Request $request, Pedido $pedido): PedidoResource
    {
        $this->authorize('view', $pedido);

        if ($pedido->estado !== EstadoPedido::Entregado) {
            throw ValidationException::withMessages(['estado' => 'Solo puedes calificar pedidos ya entregados.']);
        }
        if ($pedido->calificacion !== null) {
            throw ValidationException::withMessages(['calificacion' => 'Este pedido ya fue calificado.']);
        }

        $data = $request->validate([
            'calificacion' => ['required', 'integer', 'between:1,5'],
            'comentario' => ['nullable', 'string', 'max:500'],
        ]);

        $pedido->update([
            'calificacion' => (int) $data['calificacion'],
            'comentario_calificacion' => $data['comentario'] ?? null,
            'calificado_at' => now(),
        ]);

        return new PedidoResource($pedido->fresh(['items', 'pago', 'estados', 'zona', 'repartidor']));
    }

    public function comprobantePdf(Pedido $pedido)
    {
        $this->authorize('view', $pedido);

        $pedido->load(['items', 'pago', 'zona', 'cliente']);
        $nombre = "nota_venta_{$pedido->codigo}.pdf";

        return Pdf::loadView('pedidos.comprobante', [
            'pedido' => $pedido,
            'tienda' => config('app.name', 'Tienda Móvil'),
        ])->download($nombre);
    }
}
