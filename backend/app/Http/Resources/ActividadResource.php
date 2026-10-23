<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActividadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'aula_materia_id' => $this->aula_materia_id,
            'titulo' => $this->titulo,
            'descripcion' => $this->descripcion,
            'fecha_cumplimiento' => $this->fecha_cumplimiento?->toDateString(),
            'fecha_limite_notas' => $this->fecha_limite_notas?->toDateString(),
            'puntaje_maximo' => (float) $this->puntaje_maximo,
            'aula_materia' => $this->whenLoaded('aulaMateria', fn () => [
                'id' => $this->aulaMateria->id,
                'aula_id' => $this->aulaMateria->aula_id,
                'materia' => $this->aulaMateria->materia?->nombre,
                'profesor' => $this->aulaMateria->profesor?->nombreCompleto(),
                'aula' => $this->aulaMateria->aula?->nombre(),
            ]),
            'notas_count' => $this->whenCounted('notas'),
        ];
    }
}
