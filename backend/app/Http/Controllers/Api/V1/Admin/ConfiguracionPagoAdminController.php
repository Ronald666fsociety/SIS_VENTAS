<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracionPago;
use App\Services\AuditoriaService;
use App\Services\ImagenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ConfiguracionPagoAdminController extends Controller
{
    public function __construct(private ImagenService $imagenes, private AuditoriaService $auditoria)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $c = ConfiguracionPago::actual();
        if ($c && $c->qr_imagen_url) {
            $c->qr_imagen_url = $this->normalizarUrl($c->qr_imagen_url, $request);
        }

        return response()->json($c);
    }

    /**
     * Cambia el QR estático y datos del banco. Con archivo, usar POST multipart
     * (o POST con _method=PUT): PHP no procesa multipart en PUT.
     */
    public function update(Request $request): JsonResponse
    {
        $actual = ConfiguracionPago::actual();

        $data = $request->validate([
            'banco' => ['nullable', 'string', 'max:80'],
            'titular' => ['nullable', 'string', 'max:120'],
            'nro_cuenta' => ['nullable', 'string', 'max:40'],
            'instrucciones' => ['nullable', 'string', 'max:2000'],
            'aceptar_efectivo' => ['sometimes', 'boolean'],
            'qr_imagen' => [$actual ? 'nullable' : 'required', 'file', 'max:8192'],
        ]);

        $campos = collect($data)->except('qr_imagen')->all();
        if ($request->hasFile('qr_imagen')) {
            $disco = config('tienda.disco_publico');
            // PNG→JPEG con fondo blanco y hasta 1600 px conserva la legibilidad del QR.
            $ruta = $this->imagenes->guardar($request->file('qr_imagen'), $disco, 'qr', 1600, 'jpeg');
            $campos['qr_imagen_url'] = '/api/v1/storage/'.ltrim($ruta, '/');
        }
        $campos['updated_at'] = now();

        $config = ConfiguracionPago::updateOrCreate(['id' => 1], $campos);
        $this->auditoria->registrar($request->user(), 'configuracion_pago.actualizada', 'configuracion_pago', 1, [
            'qr_cambiado' => $request->hasFile('qr_imagen'),
            'campos' => array_keys($campos),
        ]);

        $fresh = $config->fresh();
        if ($fresh && $fresh->qr_imagen_url) {
            $fresh->qr_imagen_url = $this->normalizarUrl($fresh->qr_imagen_url, $request);
        }

        return response()->json($fresh);
    }

    private function normalizarUrl(string $url, Request $request): string
    {
        $host = $request->getSchemeAndHttpHost();
        if (str_starts_with($url, '/')) {
            return $host.$url;
        }
        if (preg_match('#^https?://[^/]+(?:/api/v1)?/storage/(.+)$#', $url, $m)) {
            return $host.'/api/v1/storage/'.$m[1];
        }
        if (str_contains($url, 'example.com')) {
            return 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=PAGO-QR-TIENDA-DEMO';
        }

        return $url;
    }
}
