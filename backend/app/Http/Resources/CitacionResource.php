<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CitacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo->value,
            'tipo_label' => $this->tipo->label(),
            'asunto' => $this->asunto,
            'mensaje' => $this->mensaje,
            'fecha_hora' => $this->fecha_hora?->toDateTimeString(),
            'lugar' => $this->lugar,
            'estado' => $this->estado->value,
            'estado_label' => $this->estado->label(),
            'leida_en' => $this->leida_en?->toDateTimeString(),
            'emisor' => $this->whenLoaded('emisor', fn () => $this->emisor?->nombreCompleto()),
            'emisor_rol' => $this->whenLoaded('emisor', fn () => $this->emisor?->rol->label()),
            'destinatario' => $this->whenLoaded('destinatario', fn () => $this->destinatario?->nombreCompleto()),
            'estudiante' => $this->whenLoaded('estudiante', fn () => $this->estudiante ? [
                'id' => $this->estudiante->id,
                'ci' => $this->estudiante->user?->ci,
                'nombre_completo' => $this->estudiante->user?->nombreCompleto(),
            ] : null),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
