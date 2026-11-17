<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $personal = (bool) $request->user()?->esPersonal();

        return [
            'id' => $this->id,
            'categoria_id' => $this->categoria_id,
            'categoria' => $this->whenLoaded('categoria', fn () => $this->categoria ? [
                'id' => $this->categoria->id,
                'nombre' => $this->categoria->nombre,
            ] : null),
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'sku' => $this->when($personal, $this->sku),
            'precio' => $this->precio,
            'precio_oferta' => $this->precio_oferta,
            'precio_final' => $this->precioFinal(),
            'unidad' => $this->unidad,
            'stock' => $this->stock,
            'agotado' => $this->stock <= 0,
            'stock_minimo' => $this->when($personal, $this->stock_minimo),
            'stock_bajo' => $this->when($personal, $this->stock <= $this->stock_minimo),
            'activo' => $this->when($personal, $this->activo),
            'destacado' => $this->destacado,
            'imagenes' => $this->whenLoaded('imagenes', fn () => $this->imagenes->map(fn ($i) => [
                'id' => $i->id,
                'url' => $this->normalizarUrl($i->url, $request),
                'orden' => $i->orden,
            ])->values()),
        ];
    }

    private function normalizarUrl(?string $url, Request $request): ?string
    {
        if (! $url) {
            return null;
        }
        $host = $request->getSchemeAndHttpHost();
        if (str_starts_with($url, '/')) {
            return $host.$url;
        }
        if (preg_match('#^https?://[^/]+/storage/(.+)$#', $url, $m)) {
            return $host.'/api/v1/storage/'.$m[1];
        }
        if (preg_match('#^https?://[^/]+/api/v1/storage/(.+)$#', $url, $m)) {
            return $host.'/api/v1/storage/'.$m[1];
        }

        return $url;
    }
}
