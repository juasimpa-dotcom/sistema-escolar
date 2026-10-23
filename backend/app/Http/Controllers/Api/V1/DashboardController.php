<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ResumenRequest;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $service) {}

    /**
     * Indicadores del establecimiento. Director, Administrativo y Superadmin.
     */
    public function resumen(ResumenRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->service->resumen($request->validated()),
        ]);
    }

    /**
     * Nota: la política del dashboard se valida en ResumenRequest (rol).
     */
    public static function rolesPermitidos(): array
    {
        return [Rol::SUPERADMIN, Rol::DIRECTOR, Rol::ADMINISTRATIVO];
    }
}
