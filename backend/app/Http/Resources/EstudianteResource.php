<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EstudianteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'unidad_educativa_id' => $this->unidad_educativa_id,
            'ci' => $this->user?->ci,
            'nombres' => $this->user?->nombres,
            'apellidos' => $this->user?->apellidos,
            'nombre_completo' => $this->user?->nombreCompleto(),
            'email' => $this->user?->email,
            'telefono' => $this->user?->telefono,
            'fecha_nacimiento' => $this->fecha_nacimiento?->toDateString(),
            'estado' => $this->estado,
            'fecha_baja' => $this->fecha_baja?->toDateString(),
            'motivo_baja' => $this->motivo_baja,
            'padres' => $this->whenLoaded('padres', function () {
                return $this->padres->map(fn ($padre) => [
                    'id' => $padre->id,
                    'ci' => $padre->ci,
                    'nombre_completo' => $padre->nombreCompleto(),
                    'telefono' => $padre->telefono,
                    'parentesco' => $padre->pivot->parentesco,
                ]);
            }),
        ];
    }
}
