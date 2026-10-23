<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EstadoLicencia;
use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Http\Requests\Licencia\AprobarLicenciaRequest;
use App\Http\Requests\Licencia\RechazarLicenciaRequest;
use App\Http\Requests\Licencia\StoreLicenciaRequest;
use App\Http\Resources\LicenciaResource;
use App\Models\Estudiante;
use App\Models\Licencia;
use App\Services\LicenciaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LicenciaController extends Controller
{
    public function __construct(private readonly LicenciaService $service) {}

    /**
     * Listado de licencias según el rol:
     * - Padre: solo las de sus hijos.
     * - Director/Administrativo: todas las de la unidad (filtrable por estado).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Licencia::class);

        $user = $request->user();

        $query = Licencia::query()
            ->select(['id', 'estudiante_id', 'padre_id', 'fecha_inicio', 'fecha_fin', 'motivo', 'estado', 'resuelto_por', 'fecha_resolucion', 'motivo_rechazo', 'created_at'])
            ->with([
                'estudiante:id,user_id',
                'estudiante.user:id,nombres,apellidos,ci',
                'padre:id,nombres,apellidos',
                'resueltoPor:id,nombres,apellidos',
            ])
            ->orderByRaw("CASE estado WHEN 'pendiente' THEN 0 WHEN 'aprobada' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at');

        if ($user->rol === Rol::PADRE) {
            $query->where('padre_id', $user->id);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->string('estado'));
        }

        if ($request->filled('estudiante_id')) {
            $query->where('estudiante_id', $request->integer('estudiante_id'));
        }

        return LicenciaResource::collection($query->paginate($request->integer('por_pagina', 15)));
    }

    public function store(StoreLicenciaRequest $request, LicenciaService $service): JsonResponse
    {
        $estudiante = Estudiante::findOrFail($request->integer('estudiante_id'));
        $this->authorize('create', [Licencia::class, $estudiante]);

        $licencia = $service->solicitar($estudiante, $request->user(), $request->validated());

        return (new LicenciaResource($licencia->load([
            'estudiante:id,user_id',
            'estudiante.user:id,nombres,apellidos,ci',
            'padre:id,nombres,apellidos',
        ])))
            ->response()->setStatusCode(201);
    }

    public function aprobar(AprobarLicenciaRequest $request, Licencia $licencia): LicenciaResource
    {
        $licencia = $this->service->aprobar($licencia, $request->user());

        return new LicenciaResource($licencia->load([
            'estudiante:id,user_id',
            'estudiante.user:id,nombres,apellidos,ci',
            'padre:id,nombres,apellidos',
            'resueltoPor:id,nombres,apellidos',
        ]));
    }

    public function rechazar(RechazarLicenciaRequest $request, Licencia $licencia): LicenciaResource
    {
        $licencia = $this->service->rechazar($licencia, $request->user(), $request->input('motivo_rechazo'));

        return new LicenciaResource($licencia->load([
            'estudiante:id,user_id',
            'estudiante.user:id,nombres,apellidos,ci',
            'padre:id,nombres,apellidos',
            'resueltoPor:id,nombres,apellidos',
        ]));
    }

    public function destroy(Request $request, Licencia $licencia): JsonResponse
    {
        $this->authorize('delete', $licencia);

        if ($licencia->estado !== EstadoLicencia::PENDIENTE) {
            abort(422, 'Solo se pueden anular solicitudes pendientes.');
        }

        $licencia->delete();

        return response()->json(['mensaje' => 'Solicitud de licencia anulada.']);
    }
}
