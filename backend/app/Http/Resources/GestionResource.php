<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'unidad_educativa_id' => $this->unidad_educativa_id,
            'anio' => $this->anio,
            'fecha_inicio' => $this->fecha_inicio?->toDateString(),
            'fecha_fin' => $this->fecha_fin?->toDateString(),
            'es_actual' => $this->es_actual,
            'estado' => $this->estado?->value,
            'estado_label' => $this->estado?->label(),
            'aulas_count' => $this->whenCounted('aulas'),
        ];
    }
}
