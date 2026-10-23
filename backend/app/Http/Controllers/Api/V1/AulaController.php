<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academico\StoreAulaRequest;
use App\Http\Requests\Academico\UpdateAulaRequest;
use App\Http\Resources\AulaResource;
use App\Models\Aula;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AulaController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Aula::class);

        $aulas = Aula::query()
            ->select(['id', 'unidad_educativa_id', 'gestion_id', 'grado', 'paralelo', 'turno', 'estado'])
            ->with(['gestion:id,anio'])
            ->withCount(['aulaMaterias', 'inscripciones'])
            ->when($request->filled('gestion_id'), fn ($q) => $q->where('gestion_id', $request->integer('gestion_id')))
            ->when($request->filled('turno'), fn ($q) => $q->where('turno', $request->string('turno')))
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->string('estado')))
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $termino = $request->string('buscar');
                $q->where(fn ($sub) => $sub
                    ->where('grado', 'ilike', "%{$termino}%")
                    ->orWhere('paralelo', 'ilike', "%{$termino}%"));
            })
            ->orderBy('grado')
            ->orderBy('paralelo')
            ->paginate($request->integer('por_pagina', 15));

        return AulaResource::collection($aulas);
    }

    public function store(StoreAulaRequest $request): JsonResponse
    {
        $aula = Aula::create($request->validated());

        return (new AulaResource($aula->load('gestion:id,anio')))->response()->setStatusCode(201);
    }

    public function show(Aula $aula): AulaResource
    {
        $this->authorize('view', $aula);

        return new AulaResource($aula->load('gestion:id,anio')->loadCount(['aulaMaterias', 'inscripciones']));
    }

    public function update(UpdateAulaRequest $request, Aula $aula): AulaResource
    {
        $aula->update($request->validated());

        return new AulaResource($aula->refresh()->load('gestion:id,anio')->loadCount(['aulaMaterias', 'inscripciones']));
    }

    public function destroy(Aula $aula): JsonResponse
    {
        $this->authorize('delete', $aula);

        $aula->delete();

        return response()->json(['mensaje' => 'Aula eliminada.']);
    }
}
