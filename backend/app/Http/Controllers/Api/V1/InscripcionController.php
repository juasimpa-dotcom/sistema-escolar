<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EstadoInscripcion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academico\InscribirRequest;
use App\Http\Requests\Academico\RetirarInscripcionRequest;
use App\Http\Resources\InscripcionResource;
use App\Models\Aula;
use App\Models\Inscripcion;
use App\Services\AcademicoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InscripcionController extends Controller
{
    public function __construct(private readonly AcademicoService $service) {}

    public function index(Aula $aula): AnonymousResourceCollection
    {
        $this->authorize('view', $aula);

        $inscripciones = $aula->inscripciones()
            ->select(['id', 'estudiante_id', 'aula_id', 'gestion_id', 'fecha_inscripcion', 'estado', 'fecha_baja', 'motivo_baja'])
            ->with(['estudiante:id,user_id', 'estudiante.user:id,nombres,apellidos,ci'])
            ->where('estado', EstadoInscripcion::INSCRITO)
            ->orderBy('id')
            ->get();

        return InscripcionResource::collection($inscripciones);
    }

    public function store(InscribirRequest $request, Aula $aula): JsonResponse
    {
        $inscripcion = $this->service->inscribir($aula, $request->integer('estudiante_id'));

        return (new InscripcionResource($inscripcion->load('estudiante.user')))
            ->response()
            ->setStatusCode(201);
    }

    public function retirar(RetirarInscripcionRequest $request, Inscripcion $inscripcion): InscripcionResource
    {
        $inscripcion = $this->service->retirar(
            $inscripcion,
            $request->validated('motivo'),
            $request->validated('fecha'),
        );

        return new InscripcionResource($inscripcion->load('estudiante.user'));
    }
}
