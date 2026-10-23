<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HorarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'aula_materia_id' => $this->aula_materia_id,
            'dia' => $this->dia?->value,
            'dia_label' => $this->dia?->label(),
            'hora_inicio' => $this->hora_inicio,
            'hora_fin' => $this->hora_fin,
            'materia' => $this->whenLoaded('aulaMateria', fn () => $this->aulaMateria->materia?->nombre),
        ];
    }
}
