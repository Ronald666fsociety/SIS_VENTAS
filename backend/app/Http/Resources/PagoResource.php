<?php

namespace App\Http\Resources;

use App\Services\PagoService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PagoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $personal = (bool) $request->user()?->esPersonal();
        $max = config('tienda.max_intentos_pago');

        return [
            'id' => $this->id,
            'pedido_id' => $this->pedido_id,
            'metodo' => $this->metodo,
            'monto' => $this->monto,
            'estado' => $this->estado->value,
            'nro_transaccion' => $this->nro_transaccion,
            'intentos' => $this->intentos,
            'intentos_restantes' => max(0, $max - $this->intentos),
            'motivo_rechazo' => $this->motivo_rechazo,
            'verificado_en' => $this->verificado_en,
            // La URL firmada y temporal del comprobante solo se entrega al personal.
            'comprobante_url' => $this->when($personal, fn () => app(PagoService::class)->urlComprobante($this->resource)),
            'tiene_comprobante' => $this->comprobante_path !== null,
            'pedido' => $this->whenLoaded('pedido', fn () => new PedidoResource($this->pedido)),
            'created_at' => $this->created_at,
        ];
    }
}
