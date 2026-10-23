<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AsistenciaHistorialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'estado_anterior' => $this->estado_anterior->value,
            'estado_anterior_label' => $this->estado_anterior->label(),
            'estado_nuevo' => $this->estado_nuevo->value,
            'estado_nuevo_label' => $this->estado_nuevo->label(),
            'observacion' => $this->observacion,
            'usuario' => $this->whenLoaded('usuario', fn () => $this->usuario?->nombreCompleto()),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
