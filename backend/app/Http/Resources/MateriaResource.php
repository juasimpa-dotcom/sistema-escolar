<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MateriaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'unidad_educativa_id' => $this->unidad_educativa_id,
            'nombre' => $this->nombre,
            'estado' => $this->estado?->value,
            'estado_label' => $this->estado?->label(),
        ];
    }
}
