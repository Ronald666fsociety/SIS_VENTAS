<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'rol' => $this->rol->value,
            'activo' => $this->activo,
            'avatar_url' => $this->avatar_url,
            'created_at' => $this->created_at,
        ];
    }
}
