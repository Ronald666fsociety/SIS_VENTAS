<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\EstadoPago;
use App\Http\Controllers\Controller;
use App\Http\Resources\PagoResource;
use App\Models\Pago;
use App\Services\PagoService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PagoAdminController extends Controller
{
    public function __construct(private PagoService $pagos)
    {
    }

    /** Bandeja de pagos. Por defecto, los pendientes de verificar (más antiguos primero). */
    public function index(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate(['estado' => ['nullable', 'in:pendiente,en_revision,verificado,rechazado']]);
        $estado = $data['estado'] ?? EstadoPago::EnRevision->value;

        $pagos = Pago::query()
            ->where('estado', $estado)
            ->with(['pedido.cliente'])
            ->orderBy('updated_at')
            ->paginate(20);

        return PagoResource::collection($pagos);
    }

    public function show(Pago $pago): PagoResource
    {
        return new PagoResource($pago->load(['pedido.cliente', 'pedido.items']));
    }

    public function aprobar(Request $request, Pago $pago): PagoResource
    {
        $pago = $this->pagos->aprobar($pago, $request->user());

        return new PagoResource($pago->load('pedido.cliente'));
    }

    public function rechazar(Request $request, Pago $pago): PagoResource
    {
        $data = $request->validate(['motivo' => ['required', 'string', 'min:3', 'max:255']]);
        $pago = $this->pagos->rechazar($pago, $request->user(), $data['motivo']);

        return new PagoResource($pago->load('pedido.cliente'));
    }
}
