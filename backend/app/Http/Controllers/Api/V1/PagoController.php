<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubirComprobanteRequest;
use App\Http\Resources\PagoResource;
use App\Models\ConfiguracionPago;
use App\Models\Pedido;
use App\Services\PagoService;
use Illuminate\Http\JsonResponse;

class PagoController extends Controller
{
    public function __construct(private PagoService $pagos)
    {
    }

    /** QR estático e instrucciones para el cliente. */
    public function configuracion(\Illuminate\Http\Request $request): JsonResponse
    {
        $c = ConfiguracionPago::actual();
        $qrUrl = $c?->qr_imagen_url;
        if ($qrUrl) {
            $host = $request->getSchemeAndHttpHost();
            if (str_starts_with($qrUrl, '/')) {
                $qrUrl = $host.$qrUrl;
            } elseif (preg_match('#^https?://[^/]+(?:/api/v1)?/storage/(.+)$#', $qrUrl, $m)) {
                $qrUrl = $host.'/api/v1/storage/'.$m[1];
            } elseif (str_contains($qrUrl, 'example.com')) {
                $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=PAGO-QR-TIENDA-DEMO';
            }
        }

        return response()->json([
            'banco' => $c?->banco,
            'titular' => $c?->titular,
            'nro_cuenta' => $c?->nro_cuenta,
            'qr_imagen_url' => $qrUrl,
            'instrucciones' => $c?->instrucciones,
            'aceptar_efectivo' => (bool) ($c?->aceptar_efectivo ?? true),
            'verificacion_minutos' => config('tienda.verificacion_minutos'),
            'max_intentos' => config('tienda.max_intentos_pago'),
        ]);
    }

    public function comprobante(SubirComprobanteRequest $request, Pedido $pedido): PagoResource
    {
        $this->authorize('subirComprobante', $pedido);

        $pago = $this->pagos->subirComprobante(
            $pedido,
            $request->file('comprobante'),
            $request->input('nro_transaccion'),
            $request->user(),
        );

        return new PagoResource($pago);
    }
}
