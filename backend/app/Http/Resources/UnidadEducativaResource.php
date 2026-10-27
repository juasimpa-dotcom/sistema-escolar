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
            'zona' => $this->zona,
            'macrodistrito_id' => $this->macrodistrito_id,
            'distrito_municipal_id' => $this->distrito_municipal_id,
            'tipo' => $this->tipo,
            'telefono' => $this->telefono,
            'estado' => $this->estado,
            'fecha_baja' => $this->fecha_baja?->toDateString(),
            'motivo_baja' => $this->motivo_baja,
            'macrodistrito' => $this->whenLoaded('macrodistrito', function () {
                $macro = $this->macrodistrito;

                if (! $macro) {
                    return null;
                }

                return [
                    'id' => $macro->id,
                    'codigo' => $macro->codigo,
                    'nombre' => $macro->nombre,
                    'tipo' => $macro->tipo,
                ];
            }),
            'distrito' => $this->whenLoaded('distrito', function () {
                $distrito = $this->distrito;

                if (! $distrito) {
                    return null;
                }

                return [
                    'id' => $distrito->id,
                    'numero' => $distrito->numero,
                    'nombre' => $distrito->nombre,
                    'macrodistrito_id' => $distrito->macrodistrito_id,
                ];
            }),
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
