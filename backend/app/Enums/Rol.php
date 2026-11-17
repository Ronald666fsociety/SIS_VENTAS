<?php

namespace App\Enums;

enum Rol: string
{
    case Cliente = 'cliente';
    case Repartidor = 'repartidor';
    case Operador = 'operador';
    case Admin = 'admin';

    /** Roles que trabajan en la App Negocio. */
    public function esPersonal(): bool
    {
        return $this !== self::Cliente;
    }

    public static function personal(): array
    {
        return [self::Repartidor, self::Operador, self::Admin];
    }
}
