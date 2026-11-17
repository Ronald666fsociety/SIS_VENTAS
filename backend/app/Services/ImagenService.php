<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ImagenService
{
    private const MIMES_PERMITIDOS = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/bmp'];

    /**
     * Valida el MIME real, re-codifica con GD (elimina metadatos/contenido embebido),
     * limita el lado mayor y guarda en el disco indicado. Devuelve la ruta relativa.
     */
    public function guardar(UploadedFile $archivo, string $disco, string $carpeta, int $maxLado = 1600, string $formato = 'jpeg'): string
    {
        $info = @getimagesize($archivo->getRealPath());
        if ($info === false || ! in_array($info['mime'], self::MIMES_PERMITIDOS, true)) {
            throw ValidationException::withMessages(['archivo' => 'El archivo no es una imagen válida (JPG, PNG, WebP o GIF).']);
        }

        $origen = match ($info['mime']) {
            'image/jpeg' => @imagecreatefromjpeg($archivo->getRealPath()),
            'image/png' => @imagecreatefrompng($archivo->getRealPath()),
            'image/webp' => @imagecreatefromwebp($archivo->getRealPath()),
            'image/gif' => function_exists('imagecreatefromgif') ? @imagecreatefromgif($archivo->getRealPath()) : false,
            'image/bmp' => function_exists('imagecreatefrombmp') ? @imagecreatefrombmp($archivo->getRealPath()) : false,
            default => false,
        };
        if (! $origen) {
            throw ValidationException::withMessages(['archivo' => 'No se pudo leer la imagen.']);
        }

        [$w, $h] = [imagesx($origen), imagesy($origen)];
        $escala = min(1, $maxLado / max($w, $h));
        $nw = max(1, (int) round($w * $escala));
        $nh = max(1, (int) round($h * $escala));

        $destino = imagecreatetruecolor($nw, $nh);
        imagefill($destino, 0, 0, imagecolorallocate($destino, 255, 255, 255)); // fondo blanco (PNG con alfa)
        imagecopyresampled($destino, $origen, 0, 0, 0, 0, $nw, $nh, $w, $h);

        ob_start();
        if ($formato === 'webp' && function_exists('imagewebp')) {
            imagewebp($destino, null, 82);
            $ext = 'webp';
        } else {
            imagejpeg($destino, null, 85);
            $ext = 'jpg';
        }
        $binario = ob_get_clean();

        $ruta = trim($carpeta, '/').'/'.bin2hex(random_bytes(16)).'.'.$ext;
        Storage::disk($disco)->put($ruta, $binario);

        return $ruta;
    }
}
