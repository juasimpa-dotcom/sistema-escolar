<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogo\StoreDistritoMunicipalRequest;
use App\Http\Requests\Catalogo\UpdateDistritoMunicipalRequest;
use App\Models\DistritoMunicipal;
use App\Models\UnidadEducativa;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DistritoMunicipalController extends Controller
{
    public function index(Request $request): LengthAwarePaginator
    {
        $this->authorize('viewAny', DistritoMunicipal::class);

        return DistritoMunicipal::query()
            ->select(['id', 'macrodistrito_id', 'numero', 'nombre'])
            ->with('macrodistrito:id,codigo,nombre,tipo')
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $termino = '%'.mb_strtolower($request->string('buscar')).'%';
                $q->where(fn ($sub) => $sub
                    ->whereRaw('lower(nombre) like ?', [$termino])
                    ->orWhereRaw('cast(numero as text) like ?', [$termino]));
            })
            ->when($request->filled('macrodistrito_id'), fn ($q) => $q->where('macrodistrito_id', $request->integer('macrodistrito_id')))
            ->orderBy('numero')
            ->paginate($request->integer('por_pagina', 15));
    }

    public function store(StoreDistritoMunicipalRequest $request): JsonResponse
    {
        $distrito = DistritoMunicipal::create($request->validated());

        return response()->json($distrito->load('macrodistrito:id,codigo,nombre'), 201);
    }

    public function show(DistritoMunicipal $distrito): JsonResponse
    {
        $this->authorize('view', $distrito);

        return response()->json($distrito->load('macrodistrito:id,codigo,nombre'));
    }

    public function update(UpdateDistritoMunicipalRequest $request, DistritoMunicipal $distrito): JsonResponse
    {
        $distrito->update($request->validated());

        return response()->json($distrito->refresh()->load('macrodistrito:id,codigo,nombre'));
    }

    public function destroy(DistritoMunicipal $distrito): JsonResponse
    {
        $this->authorize('delete', $distrito);

        $unidades = UnidadEducativa::where('distrito_municipal_id', $distrito->id)->count();

        if ($unidades > 0) {
            throw ValidationException::withMessages([
                'distrito' => ["No se puede eliminar: {$unidades} unidad(es) educativa(s) lo tienen asignado."],
            ]);
        }

        $distrito->delete();

        return response()->json(['mensaje' => 'Distrito municipal eliminado.']);
    }
}
