<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogo\StoreMacrodistritoRequest;
use App\Http\Requests\Catalogo\UpdateMacrodistritoRequest;
use App\Models\Macrodistrito;
use App\Models\Municipio;
use App\Models\UnidadEducativa;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MacrodistritoController extends Controller
{
    public function index(Request $request): LengthAwarePaginator
    {
        $this->authorize('viewAny', Macrodistrito::class);

        return Macrodistrito::query()
            ->select(['id', 'municipio_id', 'codigo', 'nombre', 'tipo'])
            ->withCount('distritosMunicipales')
            ->withCount('unidadesEducativas')
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $termino = '%'.mb_strtolower($request->string('buscar')).'%';
                $q->where(fn ($sub) => $sub
                    ->whereRaw('lower(nombre) like ?', [$termino])
                    ->orWhereRaw('lower(codigo) like ?', [$termino]));
            })
            ->when($request->filled('tipo'), fn ($q) => $q->where('tipo', $request->string('tipo')))
            ->orderBy('codigo')
            ->paginate($request->integer('por_pagina', 15));
    }

    public function store(StoreMacrodistritoRequest $request): JsonResponse
    {
        $datos = $request->validated();
        $datos['municipio_id'] = $datos['municipio_id'] ?? $this->municipioPorDefecto()->id;

        $macrodistrito = Macrodistrito::create($datos);

        return response()->json($macrodistrito->load('distritosMunicipales'), 201);
    }

    public function show(Macrodistrito $macrodistrito): JsonResponse
    {
        $this->authorize('view', $macrodistrito);

        return response()->json(
            $macrodistrito->loadCount(['distritosMunicipales', 'unidadesEducativas'])
        );
    }

    public function update(UpdateMacrodistritoRequest $request, Macrodistrito $macrodistrito): JsonResponse
    {
        $datos = $request->validated();
        $datos['municipio_id'] = $datos['municipio_id'] ?? $macrodistrito->municipio_id ?? $this->municipioPorDefecto()->id;

        $macrodistrito->update($datos);

        return response()->json($macrodistrito->refresh());
    }

    public function destroy(Macrodistrito $macrodistrito): JsonResponse
    {
        $this->authorize('delete', $macrodistrito);

        $distritos = $macrodistrito->distritosMunicipales()->count();
        $unidades = UnidadEducativa::where('macrodistrito_id', $macrodistrito->id)->count();

        if ($distritos > 0 || $unidades > 0) {
            throw ValidationException::withMessages([
                'macrodistrito' => [
                    "No se puede eliminar: está en uso por {$distritos} distrito(s) y {$unidades} unidad(es) educativa(s).",
                ],
            ]);
        }

        $macrodistrito->delete();

        return response()->json(['mensaje' => 'Macrodistrito eliminado.']);
    }

    private function municipioPorDefecto(): Municipio
    {
        return Municipio::query()->orderBy('id')->first()
            ?? Municipio::create(['nombre' => 'La Paz']);
    }
}
