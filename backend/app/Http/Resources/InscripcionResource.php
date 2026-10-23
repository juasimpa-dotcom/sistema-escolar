<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InscripcionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'estudiante_id' => $this->estudiante_id,
            'estudiante' => $this->whenLoaded('estudiante', fn () => [
                'id' => $this->estudiante->id,
                'nombre_completo' => $this->estudiante->user?->nombreCompleto(),
                'ci' => $this->estudiante->user?->ci,
            ]),
            'aula_id' => $this->aula_id,
            'gestion_id' => $this->gestion_id,
            'fecha_inscripcion' => $this->fecha_inscripcion?->toDateString(),
            'estado' => $this->estado?->value,
            'estado_label' => $this->estado?->label(),
            'fecha_baja' => $this->fecha_baja?->toDateString(),
            'motivo_baja' => $this->motivo_baja,
        ];
    }
}
