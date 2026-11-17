<?php

namespace App\Jobs;

use App\Models\Dispositivo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/** Envía un push FCM a los dispositivos de un usuario (filtrando por app: cliente | negocio). */
class EnviarPush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $userId,
        public string $titulo,
        public string $mensaje,
        public string $app = 'cliente',
        public array $datos = [],
    ) {
    }

    public function handle(): void
    {
        $tokens = Dispositivo::query()
            ->where('user_id', $this->userId)
            ->where('app', $this->app)
            ->pluck('token')
            ->all();

        if (! $tokens) {
            return;
        }

        $messagingClass = 'Kreait\\Firebase\\Contract\\Messaging';
        if (! class_exists($messagingClass) || ! config('firebase.projects.'.config('firebase.default').'.credentials')) {
            Log::info('FCM no configurado; push omitido', ['user' => $this->userId, 'titulo' => $this->titulo]);

            return;
        }

        $cm = 'Kreait\\Firebase\\Messaging\\CloudMessage';
        $notification = 'Kreait\\Firebase\\Messaging\\Notification';
        $mensaje = $cm::new()
            ->withNotification($notification::create($this->titulo, $this->mensaje))
            ->withData(array_map('strval', $this->datos));

        $reporte = app($messagingClass)->sendMulticast($mensaje, $tokens);

        // Limpia tokens inválidos
        $invalidos = array_merge($reporte->unknownTokens(), $reporte->invalidTokens());
        if ($invalidos) {
            Dispositivo::whereIn('token', $invalidos)->delete();
        }
    }
}
