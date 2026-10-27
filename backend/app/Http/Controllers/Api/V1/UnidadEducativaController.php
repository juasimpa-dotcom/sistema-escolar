<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Persona\DarDeBajaRequest;
use App\Http\Requests\UnidadEducativa\StoreUnidadEducativaRequest;
use App\Http\Requests\UnidadEducativa\UpdateUnidadEducativaRequest;
use App\Http\Resources\UnidadEducativaResource;
use App\Models\UnidadEducativa;
use App\Services\UnidadEducativaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UnidadEducativaController extends Controller
{
    public function __construct(private readonly UnidadEducativaService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', UnidadEducativa::class);

        $unidades = UnidadEducativa::query()
            ->select([
                'id', 'nombre', 'codigo', 'direccion', 'zona', 'macrodistrito_id',
                'distrito_municipal_id', 'tipo', 'telefono', 'estado',
                'fecha_baja', 'motivo_baja', 'created_at',
            ])
            ->with([
                'director:id,unidad_educativa_id,nombres,apellidos,ci,email,telefono,rol',
                'macrodistrito:id,codigo,nombre,tipo',
                'distrito:id,macrodistrito_id,numero,nombre',
            ])
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $termino = '%'.mb_strtolower($request->string('buscar')).'%';
                $q->where(fn ($sub) => $sub
                    ->whereRaw('lower(nombre) like ?', [$termino])
                    ->orWhereRaw('lower(codigo) like ?', [$termino]));
            })
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->string('estado')))
            ->when($request->filled('macrodistrito_id'), fn ($q) => $q->where('macrodistrito_id', $request->integer('macrodistrito_id')))
            ->when($request->filled('distrito_municipal_id'), fn ($q) => $q->where('distrito_municipal_id', $request->integer('distrito_municipal_id')))
            ->when($request->filled('tipo'), fn ($q) => $q->where('tipo', $request->string('tipo')))
            ->orderBy('nombre')
            ->paginate($request->integer('por_pagina', 15));

        return UnidadEducativaResource::collection($unidades);
    }

    public function store(StoreUnidadEducativaRequest $request): JsonResponse
    {
        $unidad = $this->service->crear($request->validated());

        return (new UnidadEducativaResource($unidad->load(['director', 'macrodistrito', 'distrito'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(UnidadEducativa $unidad): UnidadEducativaResource
    {
        $this->authorize('view', $unidad);

        return new UnidadEducativaResource($unidad->load(['director', 'macrodistrito', 'distrito']));
    }

    public function update(UpdateUnidadEducativaRequest $request, UnidadEducativa $unidad): UnidadEducativaResource
    {
        $unidad = $this->service->actualizar($unidad, $request->validated());

        return new UnidadEducativaResource($unidad->load(['director', 'macrodistrito', 'distrito']));
    }

    public function darDeBaja(DarDeBajaRequest $request, UnidadEducativa $unidad): UnidadEducativaResource
    {
        $this->authorize('delete', $unidad);

        $unidad = $this->service->darDeBaja($unidad, $request->validated('motivo'), $request->validated('fecha'));

        return new UnidadEducativaResource($unidad->load('director'));
    }
}
