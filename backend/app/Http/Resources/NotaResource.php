<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'actividad_id' => $this->actividad_id,
            'estudiante_id' => $this->estudiante_id,
            'estudiante' => $this->whenLoaded('estudiante', fn () => [
                'id' => $this->estudiante->id,
                'ci' => $this->estudiante->user?->ci,
                'nombre_completo' => $this->estudiante->user?->nombreCompleto(),
            ]),
            'nota' => (float) $this->nota,
            'observacion' => $this->observacion,
            'fue_modificada' => $this->fue_modificada,
            'registrado_por' => $this->whenLoaded('registradoPor', fn () => $this->registradoPor?->nombreCompleto()),
            'modificado_por' => $this->whenLoaded('modificadoPor', fn () => $this->modificadoPor?->nombreCompleto()),
            'historial' => NotaHistorialResource::collection($this->whenLoaded('historial')),
        ];
    }
}
