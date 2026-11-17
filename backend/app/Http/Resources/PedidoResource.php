<?php

namespace App\Http\Resources;

use App\Enums\EstadoPedido;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

class PedidoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $personal = (bool) $user?->esPersonal();
        $esDueno = $user && $this->user_id === $user->id;
        $telefonoCliente = $this->relationLoaded('cliente') ? $this->cliente?->telefono : null;

        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'estado' => $this->estado->value,
            'estado_etiqueta' => $this->estado->etiqueta(),
            'direccion_texto' => $this->direccion_texto,
            'referencia' => $this->referencia,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'zona' => $this->whenLoaded('zona', fn () => $this->zona ? ['id' => $this->zona->id, 'nombre' => $this->zona->nombre] : null),
            'subtotal' => $this->subtotal,
            'costo_envio' => $this->costo_envio,
            'descuento' => $this->descuento,
            'total' => $this->total,
            'notas_cliente' => $this->notas_cliente,
            // Ubicación en vivo del repartidor + distancia y ETA cuando va en camino
            'repartidor_ubicacion' => $this->when(
                $this->estado === EstadoPedido::EnCamino
                && $this->repartidor_lat !== null
                && $this->repartidor_lng !== null,
                function () {
                    $distKm = app(\App\Services\ZonaService::class)->distanciaKm(
                        (float) $this->repartidor_lat,
                        (float) $this->repartidor_lng,
                        (float) $this->lat,
                        (float) $this->lng,
                    );
                    $minutos = $this->calcularEtaMinutos($distKm);

                    return [
                        'lat' => (float) $this->repartidor_lat,
                        'lng' => (float) $this->repartidor_lng,
                        'actualizado_en' => $this->repartidor_ubicacion_at?->toIso8601String(),
                        'distancia_km' => round($distKm, 2),
                        'tiempo_estimado_min' => $minutos,
                        'texto_eta' => $this->formatearEta($minutos),
                    ];
                }
            ),
            // El código de entrega lo ve el dueño y también el admin/operador cuando va en camino.
            'codigo_entrega' => $this->when(
                ($esDueno || ($personal && ! $user?->tieneRol(\App\Enums\Rol::Repartidor))) && $this->estado === EstadoPedido::EnCamino,
                fn () => $this->codigo_entrega
            ),
            'entregado_en' => $this->entregado_en,
            'foto_entrega_url' => $this->when(
                $personal && $this->foto_entrega_url,
                fn () => $request->getSchemeAndHttpHost().URL::temporarySignedRoute(
                    'entregas.foto',
                    now()->addMinutes(config('tienda.url_comprobante_minutos')),
                    ['pedido' => $this->id],
                    absolute: false,
                )
            ),
            'cliente' => $this->when(
                $personal && $this->relationLoaded('cliente'),
                fn () => [
                    'id' => $this->cliente->id,
                    'nombre' => $this->cliente->nombre,
                    'apellido' => $this->cliente->apellido,
                    'telefono' => $this->cliente->telefono,
                    'whatsapp_url' => 'https://wa.me/'.preg_replace('/\D/', '', $this->cliente->telefono),
                ]
            ),
            'repartidor' => $this->whenLoaded('repartidor', fn () => $this->repartidor ? [
                'id' => $this->repartidor->id,
                'nombre' => $this->repartidor->nombre,
                'telefono' => $this->repartidor->telefono,
            ] : null),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($i) => [
                'producto_id' => $i->producto_id,
                'nombre_producto' => $i->nombre_producto,
                'precio_unitario' => $i->precio_unitario,
                'cantidad' => $i->cantidad,
                'subtotal' => $i->subtotal,
            ])->values()),
            'pago' => $this->whenLoaded('pago', fn () => $this->pago ? new PagoResource($this->pago) : null),
            'linea_tiempo' => $this->whenLoaded('estados', fn () => $this->estados->map(fn ($e) => [
                'estado' => $e->estado,
                'etiqueta' => EstadoPedido::from($e->estado)->etiqueta(),
                'nota' => $e->nota,
                'fecha' => $e->created_at,
            ])->values()),
            'whatsapp_soporte' => config('tienda.whatsapp_numero')
                ? 'https://wa.me/'.config('tienda.whatsapp_numero').'?text='.rawurlencode("Hola, consulta por mi pedido {$this->codigo}")
                : null,
            'calificacion' => $this->calificacion,
            'comentario_calificacion' => $this->comentario_calificacion,
            'calificado_at' => $this->calificado_at?->toIso8601String(),
            'created_at' => $this->created_at,
        ];
    }

    private function calcularEtaMinutos(float $distanciaKm): int
    {
        if ($distanciaKm <= 0.15) {
            return 1;
        }
        // Ruta urbana (<= 25 km): ~28 km/h + 2 min; ruta interdepartamental en Bolivia (> 25 km): ~60 km/h + 15 min
        if ($distanciaKm <= 25) {
            return (int) max(2, ceil(($distanciaKm / 28) * 60) + 2);
        }

        return (int) ceil(($distanciaKm / 60) * 60) + 15;
    }

    private function formatearEta(int $minutos): string
    {
        if ($minutos <= 1) {
            return 'Llegando (~1 min)';
        }
        if ($minutos < 60) {
            return "~{$minutos} min";
        }
        $horas = intdiv($minutos, 60);
        $resto = $minutos % 60;
        if ($horas < 24) {
            return $resto > 0 ? "~{$horas} h {$resto} min" : "~{$horas} h";
        }
        $dias = intdiv($horas, 24);
        $horasResto = $horas % 24;

        return $horasResto > 0 ? "~{$dias} d {$horasResto} h" : "~{$dias} d";
    }
}
