<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Persona\DarDeBajaRequest;
use App\Http\Requests\Persona\StoreEstudianteRequest;
use App\Http\Requests\Persona\UpdatePersonaRequest;
use App\Http\Resources\EstudianteResource;
use App\Models\Estudiante;
use App\Services\PersonaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EstudianteController extends Controller
{
    public function __construct(private readonly PersonaService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Estudiante::class);

        $estudiantes = Estudiante::query()
            ->select(['id', 'user_id', 'unidad_educativa_id', 'fecha_nacimiento', 'estado', 'fecha_baja', 'motivo_baja'])
            ->with([
                'user:id,nombres,apellidos,ci,email,telefono',
                'padres:id,nombres,apellidos,ci,telefono',
            ])
            ->when($request->boolean('incluir_baja'), fn ($q) => $q->withTrashed(), fn ($q) => $q->where('estado', 'activo'))
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $termino = $request->string('buscar');
                $q->whereHas('user', fn ($sub) => $sub
                    ->where('nombres', 'ilike', "%{$termino}%")
                    ->orWhere('apellidos', 'ilike', "%{$termino}%")
                    ->orWhere('ci', 'ilike', "%{$termino}%"));
            })
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->string('estado')))
            ->orderByDesc('id')
            ->paginate($request->integer('por_pagina', 15));

        return EstudianteResource::collection($estudiantes);
    }

    public function store(StoreEstudianteRequest $request): JsonResponse
    {
        $unidadId = $request->user()->unidad_educativa_id
            ?? $request->integer('unidad_educativa_id');

        abort_if($unidadId === null, 422, 'Debe indicar la unidad educativa.');

        $estudiante = $this->service->crearEstudiante($request->validated(), $unidadId);

        return (new EstudianteResource($estudiante->estudiante->load([
            'user:id,nombres,apellidos,ci,email,telefono',
            'padres:id,nombres,apellidos,ci,telefono',
        ])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Estudiante $estudiante): EstudianteResource
    {
        $this->authorize('view', $estudiante);

        return new EstudianteResource($estudiante->load([
            'user:id,nombres,apellidos,ci,email,telefono',
            'padres:id,nombres,apellidos,ci,telefono',
        ]));
    }

    public function update(UpdatePersonaRequest $request, Estudiante $estudiante): EstudianteResource
    {
        $this->authorize('update', $estudiante);

        $estudiante->user->update($request->validated());

        return new EstudianteResource($estudiante->load([
            'user:id,nombres,apellidos,ci,email,telefono',
            'padres:id,nombres,apellidos,ci,telefono',
        ]));
    }

    /**
     * Hijos del padre autenticado.
     */
    public function misHijos(Request $request): AnonymousResourceCollection
    {
        $hijos = $request->user()->hijos()
            ->select(['estudiantes.id', 'estudiantes.user_id'])
            ->with(['user:id,nombres,apellidos,ci,email,telefono'])
            ->get();

        return EstudianteResource::collection($hijos);
    }

    public function darDeBaja(DarDeBajaRequest $request, Estudiante $estudiante): EstudianteResource
    {
        $this->service->darDeBaja(
            $estudiante->user,
            $request->validated('motivo'),
            $request->validated('fecha')
        );

        return new EstudianteResource($estudiante->refresh()->load([
            'user:id,nombres,apellidos,ci,email,telefono',
            'padres:id,nombres,apellidos,ci,telefono',
        ]));
    }
}
