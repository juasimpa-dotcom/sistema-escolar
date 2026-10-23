<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academico\StoreGestionRequest;
use App\Http\Requests\Academico\UpdateGestionRequest;
use App\Http\Resources\GestionResource;
use App\Models\Gestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GestionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Gestion::class);

        $gestiones = Gestion::query()
            ->select(['id', 'unidad_educativa_id', 'anio', 'fecha_inicio', 'fecha_fin', 'es_actual', 'estado'])
            ->withCount('aulas')
            ->when($request->filled('buscar'), fn ($q) => $q->where('anio', 'ilike', '%'.$request->string('buscar').'%'))
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->string('estado')))
            ->orderByDesc('anio')
            ->paginate($request->integer('por_pagina', 15));

        return GestionResource::collection($gestiones);
    }

    public function store(StoreGestionRequest $request): JsonResponse
    {
        $gestion = Gestion::create($request->validated());

        return (new GestionResource($gestion))->response()->setStatusCode(201);
    }

    public function show(Gestion $gestion): GestionResource
    {
        $this->authorize('view', $gestion);

        return new GestionResource($gestion->loadCount('aulas'));
    }

    public function update(UpdateGestionRequest $request, Gestion $gestion): GestionResource
    {
        $gestion->update($request->validated());

        return new GestionResource($gestion->refresh()->loadCount('aulas'));
    }

    public function destroy(Gestion $gestion): JsonResponse
    {
        $this->authorize('delete', $gestion);

        $gestion->delete();

        return response()->json(['mensaje' => 'Gestión eliminada.']);
    }
}
