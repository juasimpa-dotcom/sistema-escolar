<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AulaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'unidad_educativa_id' => $this->unidad_educativa_id,
            'gestion_id' => $this->gestion_id,
            'gestion' => $this->whenLoaded('gestion', fn () => [
                'id' => $this->gestion->id,
                'anio' => $this->gestion->anio,
            ]),
            'grado' => $this->grado,
            'paralelo' => $this->paralelo,
            'nombre' => $this->nombre(),
            'turno' => $this->turno?->value,
            'turno_label' => $this->turno?->label(),
            'estado' => $this->estado?->value,
            'estado_label' => $this->estado?->label(),
            'materias_count' => $this->whenCounted('aulaMaterias'),
            'inscritos_count' => $this->whenCounted('inscripciones'),
        ];
    }
}
