<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotaHistorialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nota_anterior' => (float) $this->nota_anterior,
            'nota_nueva' => (float) $this->nota_nueva,
            'observacion' => $this->observacion,
            'usuario' => $this->whenLoaded('usuario', fn () => $this->usuario?->nombreCompleto()),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
