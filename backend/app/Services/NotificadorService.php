<?php

namespace App\Services;

use App\Enums\Rol;
use App\Jobs\EnviarPush;
use App\Models\Notificacion;
use App\Models\Pedido;
use App\Models\User;

class NotificadorService
{
    public function aUsuario(User $user, string $titulo, string $mensaje, ?Pedido $pedido = null): void
    {
        Notificacion::create([
            'user_id' => $user->id,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'pedido_id' => $pedido?->id,
        ]);

        EnviarPush::dispatch(
            $user->id,
            $titulo,
            $mensaje,
            $user->esPersonal() ? 'negocio' : 'cliente',
            array_filter(['pedido_id' => $pedido?->id, 'tipo' => 'pedido']),
        );
    }

    /** Avisa a todo el personal activo de los roles indicados (App Negocio). */
    public function aPersonal(array $roles, string $titulo, string $mensaje, ?Pedido $pedido = null): void
    {
        User::query()
            ->where('activo', true)
            ->whereIn('rol', array_map(fn (Rol $r) => $r->value, $roles))
            ->get()
            ->each(fn (User $u) => $this->aUsuario($u, $titulo, $mensaje, $pedido));
    }
}
