<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Actividad\StoreActividadRequest;
use App\Http\Requests\Actividad\UpdateActividadRequest;
use App\Http\Resources\ActividadResource;
use App\Models\Actividad;
use App\Models\AulaMateria;
use App\Services\ActividadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ActividadController extends Controller
{
    public function __construct(private readonly ActividadService $service) {}

    /**
     * Lista actividades de una materia-aula.
     */
    public function index(AulaMateria $aulaMateria): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Actividad::class);

        $actividades = Actividad::query()
            ->select(['id', 'aula_materia_id', 'titulo', 'descripcion', 'fecha_cumplimiento', 'fecha_limite_notas', 'puntaje_maximo'])
            ->where('aula_materia_id', $aulaMateria->id)
            ->with([
                'aulaMateria:id,aula_id,materia_id,profesor_id',
                'aulaMateria.materia:id,nombre',
                'aulaMateria.profesor:id,nombres,apellidos',
                'aulaMateria.aula:id,grado,paralelo',
            ])
            ->withCount('notas')
            ->orderBy('fecha_cumplimiento')
            ->get();

        return ActividadResource::collection($actividades);
    }

    /**
     * Actividades del profesor autenticado, ordenadas por fecha límite de notas.
     */
    public function misActividades(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Actividad::class);

        $user = $request->user();

        $actividades = Actividad::query()
            ->select(['id', 'aula_materia_id', 'titulo', 'descripcion', 'fecha_cumplimiento', 'fecha_limite_notas', 'puntaje_maximo'])
            ->whereHas('aulaMateria', fn ($q) => $q->where('profesor_id', $user->id))
            ->with([
                'aulaMateria:id,aula_id,materia_id,profesor_id',
                'aulaMateria.materia:id,nombre',
                'aulaMateria.profesor:id,nombres,apellidos',
                'aulaMateria.aula:id,grado,paralelo',
            ])
            ->withCount('notas')
            ->when($request->filled('buscar'), fn ($q) => $q->where('titulo', 'ilike', '%'.$request->string('buscar').'%'))
            ->orderByRaw('fecha_limite_notas IS NULL')
            ->orderBy('fecha_limite_notas')
            ->orderBy('fecha_cumplimiento')
            ->paginate($request->integer('por_pagina', 15));

        return ActividadResource::collection($actividades);
    }

    public function store(StoreActividadRequest $request, AulaMateria $aulaMateria): JsonResponse
    {
        $actividad = $this->service->crear($aulaMateria, $request->validated());

        return (new ActividadResource(
            $actividad->load([
                'aulaMateria:id,aula_id,materia_id,profesor_id',
                'aulaMateria.materia:id,nombre',
                'aulaMateria.profesor:id,nombres,apellidos',
                'aulaMateria.aula:id,grado,paralelo',
            ])->loadCount('notas')
        ))->response()->setStatusCode(201);
    }

    public function show(Actividad $actividad): ActividadResource
    {
        $this->authorize('view', $actividad);

        return new ActividadResource(
            $actividad->load([
                'aulaMateria:id,aula_id,materia_id,profesor_id',
                'aulaMateria.materia:id,nombre',
                'aulaMateria.profesor:id,nombres,apellidos',
                'aulaMateria.aula:id,grado,paralelo',
            ])->loadCount('notas')
        );
    }

    public function update(UpdateActividadRequest $request, Actividad $actividad): ActividadResource
    {
        $actividad = $this->service->actualizar($actividad, $request->validated());

        return new ActividadResource(
            $actividad->load([
                'aulaMateria:id,aula_id,materia_id,profesor_id',
                'aulaMateria.materia:id,nombre',
                'aulaMateria.profesor:id,nombres,apellidos',
                'aulaMateria.aula:id,grado,paralelo',
            ])->loadCount('notas')
        );
    }

    public function destroy(Actividad $actividad): JsonResponse
    {
        $this->authorize('delete', $actividad);

        $this->service->eliminar($actividad);

        return response()->json(['mensaje' => 'Actividad eliminada.']);
    }
}
