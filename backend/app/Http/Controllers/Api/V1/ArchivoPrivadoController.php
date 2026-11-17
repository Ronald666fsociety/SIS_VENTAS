<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Pago;
use App\Models\Pedido;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Sirve archivos del disco privado mediante URLs firmadas y archivos del disco público. */
class ArchivoPrivadoController extends Controller
{
    public function comprobante(Pago $pago): StreamedResponse
    {
        abort_unless($pago->comprobante_path, 404);

        return $this->enviar($pago->comprobante_path);
    }

    public function fotoEntrega(Pedido $pedido): StreamedResponse
    {
        abort_unless($pedido->foto_entrega_url, 404);

        return $this->enviar($pedido->foto_entrega_url);
    }

    public function publico(string $path): StreamedResponse
    {
        $disco = Storage::disk(config('tienda.disco_publico'));
        abort_unless($disco->exists($path), 404);

        return $disco->response($path, null, [
            'Cache-Control' => 'public, max-age=86400',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    private function enviar(string $ruta): StreamedResponse
    {
        $disco = Storage::disk(config('tienda.disco_privado'));
        abort_unless($disco->exists($ruta), 404);

        return $disco->response($ruta, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }
}
