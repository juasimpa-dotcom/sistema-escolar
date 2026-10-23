<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academico\StoreHorarioRequest;
use App\Http\Requests\Academico\UpdateHorarioRequest;
use App\Http\Resources\HorarioResource;
use App\Models\Aula;
use App\Models\AulaMateria;
use App\Models\Horario;
use App\Services\HorarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class HorarioController extends Controller
{
    public function __construct(private readonly HorarioService $service) {}

    public function index(Aula $aula): AnonymousResourceCollection
    {
        $this->authorize('view', $aula);

        $horarios = Horario::query()
            ->select(['id', 'aula_materia_id', 'dia', 'hora_inicio', 'hora_fin'])
            ->whereHas('aulaMateria', fn ($q) => $q->where('aula_id', $aula->id))
            ->with([
                'aulaMateria:id,materia_id',
                'aulaMateria.materia:id,nombre',
                'aulaMateria.profesor:id,nombres,apellidos',
            ])
            ->orderBy('dia')
            ->orderBy('hora_inicio')
            ->get();

        return HorarioResource::collection($horarios);
    }

    public function store(StoreHorarioRequest $request, Aula $aula): JsonResponse
    {
        $aulaMateria = AulaMateria::where('aula_id', $aula->id)
            ->findOrFail($request->integer('aula_materia_id'));

        $horario = $this->service->registrar(
            $aulaMateria,
            $request->string('dia'),
            $request->string('hora_inicio'),
            $request->string('hora_fin'),
        );

        return (new HorarioResource(
            $horario->load([
                'aulaMateria:id,materia_id',
                'aulaMateria.materia:id,nombre',
                'aulaMateria.profesor:id,nombres,apellidos',
            ])
        ))->response()->setStatusCode(201);
    }

    public function update(UpdateHorarioRequest $request, Horario $horario): HorarioResource
    {
        $horario = $this->service->actualizar(
            $horario,
            $request->string('dia'),
            $request->string('hora_inicio'),
            $request->string('hora_fin'),
        );

        return new HorarioResource($horario->load([
            'aulaMateria:id,materia_id',
            'aulaMateria.materia:id,nombre',
            'aulaMateria.profesor:id,nombres,apellidos',
        ]));
    }

    public function destroy(Horario $horario): JsonResponse
    {
        $this->authorize('gestionarHorario', $horario->aulaMateria->aula);

        $this->service->eliminar($horario);

        return response()->json(['mensaje' => 'Bloque de horario eliminado.']);
    }
}
