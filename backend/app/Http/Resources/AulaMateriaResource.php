<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AulaMateriaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'aula_id' => $this->aula_id,
            'materia_id' => $this->materia_id,
            'materia' => $this->whenLoaded('materia', fn () => [
                'id' => $this->materia->id,
                'nombre' => $this->materia->nombre,
            ]),
            'profesor_id' => $this->profesor_id,
            'profesor' => $this->whenLoaded('profesor', fn () => [
                'id' => $this->profesor->id,
                'nombre_completo' => $this->profesor->nombreCompleto(),
            ]),
            'aula' => $this->whenLoaded('aula', fn () => [
                'id' => $this->aula->id,
                'nombre' => $this->aula->nombre(),
            ]),
            'horarios' => HorarioResource::collection($this->whenLoaded('horarios')),
        ];
    }
}
