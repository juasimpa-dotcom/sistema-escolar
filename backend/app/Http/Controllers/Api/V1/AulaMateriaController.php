<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academico\AsignarMateriaRequest;
use App\Http\Resources\AulaMateriaResource;
use App\Models\Aula;
use App\Models\AulaMateria;
use App\Services\AcademicoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AulaMateriaController extends Controller
{
    public function __construct(private readonly AcademicoService $service) {}

    public function index(Aula $aula): AnonymousResourceCollection
    {
        $this->authorize('view', $aula);

        $asignaciones = $aula->aulaMaterias()
            ->select(['id', 'aula_id', 'materia_id', 'profesor_id'])
            ->with([
                'materia:id,nombre',
                'profesor:id,nombres,apellidos',
                'horarios:id,aula_materia_id,dia,hora_inicio,hora_fin',
                'horarios.aulaMateria:id,materia_id',
                'horarios.aulaMateria.materia:id,nombre',
            ])
            ->get();

        return AulaMateriaResource::collection($asignaciones);
    }

    public function store(AsignarMateriaRequest $request, Aula $aula): JsonResponse
    {
        $asignacion = $this->service->asignarMateriaAAula(
            $aula,
            $request->integer('materia_id'),
            $request->integer('profesor_id'),
        );

        return (new AulaMateriaResource(
            $asignacion->load([
                'materia:id,nombre',
                'profesor:id,nombres,apellidos',
                'horarios:id,aula_materia_id,dia,hora_inicio,hora_fin',
                'horarios.aulaMateria:id,materia_id',
                'horarios.aulaMateria.materia:id,nombre',
            ])
        ))->response()->setStatusCode(201);
    }

    public function destroy(Aula $aula, AulaMateria $aulaMateria): JsonResponse
    {
        $this->authorize('gestionarMaterias', $aula);

        $this->service->quitarMateriaDeAula($aula, $aulaMateria);

        return response()->json(['mensaje' => 'Materia quitada del aula.']);
    }
}
