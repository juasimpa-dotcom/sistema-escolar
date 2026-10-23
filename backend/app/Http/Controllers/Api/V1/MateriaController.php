<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academico\StoreMateriaRequest;
use App\Http\Requests\Academico\UpdateMateriaRequest;
use App\Http\Resources\MateriaResource;
use App\Models\Materia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MateriaController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Materia::class);

        $materias = Materia::query()
            ->select(['id', 'unidad_educativa_id', 'nombre', 'estado'])
            ->when($request->filled('buscar'), fn ($q) => $q->where('nombre', 'ilike', '%'.$request->string('buscar').'%'))
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->string('estado')))
            ->orderBy('nombre')
            ->paginate($request->integer('por_pagina', 15));

        return MateriaResource::collection($materias);
    }

    public function store(StoreMateriaRequest $request): JsonResponse
    {
        $materia = Materia::create($request->validated());

        return (new MateriaResource($materia))->response()->setStatusCode(201);
    }

    public function show(Materia $materia): MateriaResource
    {
        $this->authorize('view', $materia);

        return new MateriaResource($materia);
    }

    public function update(UpdateMateriaRequest $request, Materia $materia): MateriaResource
    {
        $materia->update($request->validated());

        return new MateriaResource($materia->refresh());
    }

    public function destroy(Materia $materia): JsonResponse
    {
        $this->authorize('delete', $materia);

        $materia->delete();

        return response()->json(['mensaje' => 'Materia eliminada.']);
    }
}
