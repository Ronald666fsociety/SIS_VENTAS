<?php

namespace App\Policies;

use App\Models\Direccion;
use App\Models\User;

class DireccionPolicy
{
    public function view(User $user, Direccion $direccion): bool
    {
        return $direccion->user_id === $user->id;
    }

    public function update(User $user, Direccion $direccion): bool
    {
        return $direccion->user_id === $user->id;
    }

    public function delete(User $user, Direccion $direccion): bool
    {
        return $direccion->user_id === $user->id;
    }
}
