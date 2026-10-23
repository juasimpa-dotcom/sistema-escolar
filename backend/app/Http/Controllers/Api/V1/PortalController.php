<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Services\PortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    public function __construct(private readonly PortalService $service) {}

    /**
     * Portal del estudiante: el propio estudiante ve su información (solo lectura).
     */
    public function miPerfil(Request $request): JsonResponse
    {
        $estudiante = Estudiante::query()
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json(['data' => $this->service->resumen($estudiante)]);
    }

    /**
     * Portal de familia: el padre consulta a uno de sus hijos.
     */
    public function hijo(Request $request, Estudiante $estudiante): JsonResponse
    {
        $user = $request->user();

        $esTutor = $user->rol === Rol::PADRE
            && $user->hijos()->whereKey($estudiante->id)->exists();

        abort_unless($esTutor || $user->rol === Rol::SUPERADMIN, 403, 'No tiene acceso a este estudiante.');

        return response()->json(['data' => $this->service->resumen($estudiante)]);
    }
}
