<?php

namespace App\Policies;

use App\Enums\Rol;
use App\Models\Pedido;
use App\Models\User;

class PedidoPolicy
{
    /** El cliente solo ve y opera sus propios pedidos. */
    public function view(User $user, Pedido $pedido): bool
    {
        return $pedido->user_id === $user->id;
    }

    public function cancelar(User $user, Pedido $pedido): bool
    {
        return $pedido->user_id === $user->id;
    }

    public function subirComprobante(User $user, Pedido $pedido): bool
    {
        return $pedido->user_id === $user->id;
    }

    /** El repartidor solo opera pedidos que le asignaron. */
    public function gestionarEntrega(User $user, Pedido $pedido): bool
    {
        return $user->tieneRol(Rol::Repartidor) && $pedido->repartidor_id === $user->id;
    }
}
