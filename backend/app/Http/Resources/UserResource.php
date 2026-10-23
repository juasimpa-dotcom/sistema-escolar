<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'unidad_educativa_id' => $this->unidad_educativa_id,
            'rol' => $this->rol,
            'rol_label' => $this->rol?->label(),
            'ci' => $this->ci,
            'usuario' => $this->usuario,
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'nombre_completo' => $this->nombreCompleto(),
            'email' => $this->email,
            'telefono' => $this->telefono,
            'debe_cambiar_password' => $this->debe_cambiar_password,
            'estado' => $this->estado,
            'fecha_baja' => $this->fecha_baja?->toDateString(),
            'motivo_baja' => $this->motivo_baja,
            'unidad_educativa' => $this->whenLoaded('unidadEducativa', function () {
                return [
                    'id' => $this->unidadEducativa->id,
                    'nombre' => $this->unidadEducativa->nombre,
                    'codigo' => $this->unidadEducativa->codigo,
                ];
            }),
            'hijos' => $this->whenLoaded('hijos', function () {
                return $this->hijos->map(fn ($hijo) => [
                    'id' => $hijo->id,
                    'nombre_completo' => $hijo->nombreCompleto(),
                    'ci' => $hijo->ci,
                    'parentesco' => $hijo->pivot->parentesco,
                ]);
            }),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
