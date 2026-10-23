<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LicenciaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'estudiante_id' => $this->estudiante_id,
            'estudiante' => $this->whenLoaded('estudiante', fn () => [
                'id' => $this->estudiante->id,
                'ci' => $this->estudiante->user?->ci,
                'nombre_completo' => $this->estudiante->user?->nombreCompleto(),
            ]),
            'padre' => $this->whenLoaded('padre', fn () => $this->padre?->nombreCompleto()),
            'fecha_inicio' => $this->fecha_inicio?->toDateString(),
            'fecha_fin' => $this->fecha_fin?->toDateString(),
            'motivo' => $this->motivo,
            'estado' => $this->estado->value,
            'estado_label' => $this->estado->label(),
            'resuelto_por' => $this->whenLoaded('resueltoPor', fn () => $this->resueltoPor?->nombreCompleto()),
            'fecha_resolucion' => $this->fecha_resolucion?->toDateTimeString(),
            'motivo_rechazo' => $this->motivo_rechazo,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
