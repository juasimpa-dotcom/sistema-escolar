<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnidadEducativaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'codigo' => $this->codigo,
            'direccion' => $this->direccion,
            'telefono' => $this->telefono,
            'estado' => $this->estado,
            'fecha_baja' => $this->fecha_baja?->toDateString(),
            'motivo_baja' => $this->motivo_baja,
            'director' => $this->whenLoaded('director', function () {
                $director = $this->director;

                if (! $director) {
                    return null;
                }

                return [
                    'id' => $director->id,
                    'ci' => $director->ci,
                    'nombre_completo' => $director->nombreCompleto(),
                    'email' => $director->email,
                    'telefono' => $director->telefono,
                ];
            }),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
