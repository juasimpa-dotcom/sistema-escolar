<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AsistenciaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'aula_materia_id' => $this->aula_materia_id,
            'estudiante_id' => $this->estudiante_id,
            'estudiante' => $this->whenLoaded('estudiante', fn () => [
                'id' => $this->estudiante->id,
                'ci' => $this->estudiante->user?->ci,
                'nombre_completo' => $this->estudiante->user?->nombreCompleto(),
            ]),
            'fecha' => $this->fecha?->toDateString(),
            'estado' => $this->estado->value,
            'estado_label' => $this->estado->label(),
            'observacion' => $this->observacion,
            'registrado_por' => $this->whenLoaded('registradoPor', fn () => $this->registradoPor?->nombreCompleto()),
            'modificado_por' => $this->whenLoaded('modificadoPor', fn () => $this->modificadoPor?->nombreCompleto()),
            'historial' => AsistenciaHistorialResource::collection($this->whenLoaded('historial')),
        ];
    }
}
