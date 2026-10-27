<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DistritoMunicipal;
use App\Models\Aula;
use App\Models\Estudiante;
use App\Models\Gestion;
use App\Models\Macrodistrito;
use App\Models\Periodo;
use App\Models\UnidadEducativa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Catálogos geográficos planos para reutilizar en filtros de
 * dashboard, reportes y formularios.
 */
class CatalogoController extends Controller
{
    public function macrodistritos(): JsonResponse
    {
        return response()->json(
            Macrodistrito::query()
                ->orderBy('codigo')
                ->get(['id', 'municipio_id', 'codigo', 'nombre', 'tipo'])
        );
    }

    public function distritos(Request $request): JsonResponse
    {
        return response()->json(
            DistritoMunicipal::query()
                ->when($request->filled('macrodistrito_id'), fn ($q) => $q->where('macrodistrito_id', $request->integer('macrodistrito_id')))
                ->orderBy('numero')
                ->get(['id', 'macrodistrito_id', 'numero', 'nombre'])
        );
    }

    public function unidadesEducativas(Request $request): JsonResponse
    {
        abort_unless($request->user()->rol->value === 'superadmin', 403);

        return response()->json(UnidadEducativa::query()
            ->when($request->filled('macrodistrito_id'), fn ($q) => $q->where('macrodistrito_id', $request->integer('macrodistrito_id')))
            ->when($request->filled('distrito_id'), fn ($q) => $q->where('distrito_municipal_id', $request->integer('distrito_id')))
            ->orderBy('nombre')->get(['id', 'nombre']));
    }

    public function gestiones(): JsonResponse
    {
        return response()->json(Gestion::query()->orderByDesc('anio')->get(['id', 'anio', 'es_actual']));
    }

    public function aulas(): JsonResponse
    {
        return response()->json(Aula::query()->with('gestion:id,anio')->orderBy('grado')->orderBy('paralelo')->get(['id','gestion_id','grado','paralelo','nivel']));
    }

    public function estudiantes(): JsonResponse
    {
        return response()->json(Estudiante::query()->with('user:id,nombres,apellidos')->orderBy('id')->get(['id','user_id']));
    }

    public function periodos(): JsonResponse
    {
        return response()->json(Periodo::query()->orderByDesc('fecha_inicio')->get(['id','gestion_id','nombre']));
    }
}
