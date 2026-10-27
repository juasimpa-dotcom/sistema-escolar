<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ResumenRequest;
use App\Http\Requests\Dashboard\TableroRequest;
use App\Http\Resources\Dashboard\DashboardResource;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $service) {}

    /**
     * Panel de inicio por rol (GET /dashboard).
     *
     * Devuelve la sección que corresponde al rol autenticado con las
     * consultas agregadas ya cacheadas por DashboardService.
     */
    public function tablero(TableroRequest $request): DashboardResource
    {
        $user = $request->user();

        return new DashboardResource(
            $this->service->tablero($user->rol, $user->id, $request->validated())
        );
    }

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
