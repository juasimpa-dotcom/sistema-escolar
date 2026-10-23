<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EstadoInscripcion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Nota\GuardarPlanillaRequest;
use App\Http\Requests\Nota\ModificarNotaRequest;
use App\Http\Requests\Nota\StoreNotaRequest;
use App\Http\Resources\ActividadResource;
use App\Http\Resources\NotaHistorialResource;
use App\Http\Resources\NotaResource;
use App\Models\Actividad;
use App\Models\Inscripcion;
use App\Models\Nota;
use App\Services\NotaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotaController extends Controller
{
    public function __construct(private readonly NotaService $service) {}

    /**
     * Planilla completa: todos los inscritos del aula con su nota (si existe).
     */
    public function planilla(Actividad $actividad): JsonResponse
    {
        $this->authorize('view', $actividad);

        $actividad->load('aulaMateria.aula');

        $inscritos = Inscripcion::query()
            ->select(['id', 'estudiante_id', 'aula_id', 'gestion_id'])
            ->where('aula_id', $actividad->aulaMateria->aula_id)
            ->where('gestion_id', $actividad->aulaMateria->aula->gestion_id)
            ->where('estado', EstadoInscripcion::INSCRITO)
            ->with(['estudiante:id,user_id', 'estudiante.user:id,nombres,apellidos,ci'])
            ->get();

        $notas = Nota::query()
            ->select(['id', 'actividad_id', 'estudiante_id', 'nota', 'fue_modificada'])
            ->where('actividad_id', $actividad->id)
            ->get()
            ->keyBy('estudiante_id');

        $filas = $inscritos->map(function ($inscripcion) use ($notas) {
            $nota = $notas->get($inscripcion->estudiante_id);

            return [
                'estudiante_id' => $inscripcion->estudiante_id,
                'ci' => $inscripcion->estudiante?->user?->ci,
                'nombre_completo' => $inscripcion->estudiante?->user?->nombreCompleto(),
                'nota_id' => $nota?->id,
                'nota' => $nota ? (float) $nota->nota : null,
                'fue_modificada' => $nota?->fue_modificada ?? false,
            ];
        })->values();

        return response()->json([
            'data' => [
                'actividad' => (new ActividadResource(
                    $actividad->load(['aulaMateria.materia', 'aulaMateria.profesor'])
                ))->resolve(),
                'filas' => $filas,
            ],
        ]);
    }

    public function store(StoreNotaRequest $request, Actividad $actividad): JsonResponse
    {
        $nota = $this->service->registrar(
            $actividad,
            $request->integer('estudiante_id'),
            (float) $request->input('nota'),
            $request->user(),
        );

        return (new NotaResource($nota->load('estudiante.user')))->response()->setStatusCode(201);
    }

    /**
     * Guarda varias notas a la vez (planilla del profesor).
     */
    public function guardarPlanilla(GuardarPlanillaRequest $request, Actividad $actividad): JsonResponse
    {
        $profesor = $request->user();

        foreach ($request->input('notas') as $fila) {
            $this->service->registrar(
                $actividad,
                (int) $fila['estudiante_id'],
                (float) $fila['nota'],
                $profesor,
            );
        }

        return response()->json(['mensaje' => 'Planilla guardada.']);
    }

    public function modificar(ModificarNotaRequest $request, Nota $nota): NotaResource
    {
        $nota = $this->service->modificar(
            $nota,
            (float) $request->input('nota'),
            $request->input('observacion'),
            $request->user(),
        );

        return new NotaResource($nota->load(['estudiante:id,user_id', 'estudiante.user:id,nombres,apellidos,ci', 'historial.usuario:id,nombres,apellidos']));
    }

    public function historial(Nota $nota): AnonymousResourceCollection
    {
        $this->authorize('view', $nota);

        return NotaHistorialResource::collection(
            $nota->historial()->select(['id', 'nota_id', 'nota_anterior', 'nota_nueva', 'observacion', 'usuario_id', 'created_at'])->with('usuario:id,nombres,apellidos')->orderByDesc('created_at')->get()
        );
    }
}
