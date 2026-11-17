<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\User;

class AuditoriaService
{
    public function registrar(?User $user, string $accion, ?string $entidad = null, ?int $entidadId = null, array $detalle = []): void
    {
        Auditoria::create([
            'user_id' => $user?->id,
            'accion' => $accion,
            'entidad' => $entidad,
            'entidad_id' => $entidadId,
            'detalle' => $detalle ?: null,
            'ip' => request()?->ip(),
        ]);
    }
}
