<?php

namespace App\Http\Resources\Dashboard;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Envuelve el tablero cacheado de DashboardService.
 *
 * El servicio guarda arreglos planos (cacheables); este Resource los expone
 * con los metadatos de sesión y filtros aplicados.
 *
 * @mixin DashboardService
 */
class DashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'rol' => $this->resource['rol'] ?? null,
            'generado_en' => $this->resource['generado_en'] ?? null,
            'filtros' => $this->resource['filtros'] ?? [],
            'datos' => $this->resource['datos'] ?? [],
        ];
    }
}
