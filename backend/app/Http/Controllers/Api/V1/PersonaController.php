<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Http\Requests\Persona\DarDeBajaRequest;
use App\Http\Requests\Persona\StorePersonaRequest;
use App\Http\Requests\Persona\UpdatePersonaRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\PersonaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PersonaController extends Controller
{
    public function __construct(private readonly PersonaService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);

        $roles = $request->filled('rol')
            ? [$request->string('rol')->toString()]
            : [Rol::PROFESOR->value, Rol::ADMINISTRATIVO->value, Rol::PADRE->value];

        $personas = User::query()
            ->select(['id', 'unidad_educativa_id', 'rol', 'ci', 'usuario', 'nombres', 'apellidos', 'email', 'telefono', 'debe_cambiar_password', 'estado', 'fecha_baja', 'motivo_baja', 'created_at'])
            ->with(['unidadEducativa:id,nombre,codigo'])
            ->whereIn('rol', $roles)
            ->when($request->boolean('incluir_baja'), fn ($q) => $q->withTrashed(), fn ($q) => $q->where('estado', 'activo'))
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $termino = $request->string('buscar');
                $q->where(fn ($sub) => $sub
                    ->where('nombres', 'ilike', "%{$termino}%")
                    ->orWhere('apellidos', 'ilike', "%{$termino}%")
                    ->orWhere('ci', 'ilike', "%{$termino}%"));
            })
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->string('estado')))
            ->orderBy('apellidos')
            ->paginate($request->integer('por_pagina', 15));

        return UserResource::collection($personas);
    }

    public function store(StorePersonaRequest $request): JsonResponse
    {
        $unidadId = $request->user()->unidad_educativa_id
            ?? $request->integer('unidad_educativa_id');

        abort_if($unidadId === null, 422, 'Debe indicar la unidad educativa.');

        $persona = $this->service->crearPersona(
            $request->validated(),
            Rol::from($request->validated('rol')),
            $unidadId
        );

        return (new UserResource($persona->load('unidadEducativa:id,nombre,codigo')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(User $persona): UserResource
    {
        $this->authorize('view', $persona);

        return new UserResource($persona->load([
            'unidadEducativa:id,nombre,codigo',
            'hijos:id,user_id',
        ]));
    }

    public function update(UpdatePersonaRequest $request, User $persona): UserResource
    {
        $persona->update($request->validated());

        return new UserResource($persona->load([
            'unidadEducativa:id,nombre,codigo',
            'hijos:id,user_id',
        ]));
    }

    public function darDeBaja(DarDeBajaRequest $request, User $persona): UserResource
    {
        $persona = $this->service->darDeBaja(
            $persona,
            $request->validated('motivo'),
            $request->validated('fecha')
        );

        return new UserResource($persona->load('unidadEducativa:id,nombre,codigo'));
    }
}
