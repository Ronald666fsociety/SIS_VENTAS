<?php

namespace App\Enums;

enum EstadoPago: string
{
    case Pendiente = 'pendiente';
    case EnRevision = 'en_revision';
    case Verificado = 'verificado';
    case Rechazado = 'rechazado';
}
