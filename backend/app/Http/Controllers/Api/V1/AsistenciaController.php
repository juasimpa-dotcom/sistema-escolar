<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EstadoAsistencia;
use App\Enums\EstadoInscripcion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Asistencia\ModificarAsistenciaRequest;
use App\Http\Requests\Asistencia\RegistrarTomaRequest;
use App\Http\Resources\AsistenciaHistorialResource;
use App\Http\Resources\AsistenciaResource;
use App\Http\Resources\AulaMateriaResource;
use App\Models\Asistencia;
use App\Models\AulaMateria;
use App\Models\Inscripcion;
use App\Models\Licencia;
use App\Services\AsistenciaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AsistenciaController extends Controller
{
    public function __construct(private readonly AsistenciaService $service) {}

    /**
     * Materias-aula que imparte el profesor autenticado.
     */
    public function misAsignaciones(Request $request): AnonymousResourceCollection
    {
        $asignaciones = AulaMateria::query()
            ->select(['id', 'aula_id', 'materia_id', 'profesor_id'])
            ->where('profesor_id', $request->user()->id)
            ->with([
                'materia:id,nombre',
                'profesor:id,nombres,apellidos',
                'aula:id,grado,paralelo,turno',
            ])
            ->get();

        return AulaMateriaResource::collection($asignaciones);
    }

    /**
     * Planilla de toma de asistencia para una fecha: estudiantes inscritos
     * con su estado actual (si existe) y si tienen licencia aprobada.
     */
    public function planilla(Request $request, AulaMateria $aulaMateria): JsonResponse
    {
        $this->authorize('registrar', [Asistencia::class, $aulaMateria]);

        $fecha = $request->input('fecha', now()->toDateString());

        $aulaMateria->load([
            'aula:id,gestion_id,grado,paralelo',
            'materia:id,nombre',
            'profesor:id,nombres,apellidos',
        ]);

        $inscritos = Inscripcion::query()
            ->select(['id', 'estudiante_id', 'aula_id', 'gestion_id'])
            ->where('aula_id', $aulaMateria->aula_id)
            ->where('gestion_id', $aulaMateria->aula->gestion_id)
            ->where('estado', EstadoInscripcion::INSCRITO)
            ->with(['estudiante:id,user_id', 'estudiante.user:id,nombres,apellidos,ci'])
            ->get();

        $asistencias = Asistencia::query()
            ->select(['id', 'aula_materia_id', 'estudiante_id', 'fecha', 'estado', 'observacion'])
            ->where('aula_materia_id', $aulaMateria->id)
            ->where('fecha', $fecha)
            ->get()
            ->keyBy('estudiante_id');

        $conLicencia = Licencia::estudiantesConLicenciaEn(
            $inscritos->pluck('estudiante_id')->all(),
            $fecha,
        );

        $filas = $inscritos->map(function ($inscripcion) use ($asistencias, $conLicencia) {
            $asistencia = $asistencias->get($inscripcion->estudiante_id);
            $tieneLicencia = in_array($inscripcion->estudiante_id, $conLicencia, true);

            return [
                'estudiante_id' => $inscripcion->estudiante_id,
                'ci' => $inscripcion->estudiante?->user?->ci,
                'nombre_completo' => $inscripcion->estudiante?->user?->nombreCompleto(),
                'asistencia_id' => $asistencia?->id,
                'estado' => $asistencia?->estado->value ?? ($tieneLicencia ? EstadoAsistencia::LICENCIA->value : null),
                'observacion' => $asistencia?->observacion,
                'tiene_licencia' => $tieneLicencia,
            ];
        })->values();

        return response()->json([
            'data' => [
                'aula_materia' => [
                    'id' => $aulaMateria->id,
                    'materia' => $aulaMateria->materia?->nombre,
                    'profesor' => $aulaMateria->profesor?->nombreCompleto(),
                    'aula' => $aulaMateria->aula?->nombre(),
                ],
                'fecha' => $fecha,
                'filas' => $filas,
            ],
        ]);
    }

    public function tomar(RegistrarTomaRequest $request, AulaMateria $aulaMateria): JsonResponse
    {
        $this->service->registrarToma(
            $aulaMateria,
            $request->input('fecha'),
            $request->input('registros'),
            $request->user(),
        );

        return response()->json(['mensaje' => 'Asistencia registrada.']);
    }

    /**
     * Listado de asistencias de un aula-materia (filtrable por fecha).
     */
    public function index(Request $request, AulaMateria $aulaMateria): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Asistencia::class);

        $query = Asistencia::query()
            ->select(['id', 'aula_materia_id', 'estudiante_id', 'fecha', 'estado', 'observacion'])
            ->where('aula_materia_id', $aulaMateria->id)
            ->with(['estudiante:id,user_id', 'estudiante.user:id,nombres,apellidos,ci'])
            ->orderByDesc('fecha');

        if ($request->filled('fecha')) {
            $query->where('fecha', $request->date('fecha')->toDateString());
        }

        return AsistenciaResource::collection($query->paginate($request->integer('por_pagina', 30)));
    }

    public function modificar(ModificarAsistenciaRequest $request, Asistencia $asistencia): AsistenciaResource
    {
        $asistencia = $this->service->modificar(
            $asistencia,
            EstadoAsistencia::from($request->input('estado')),
            $request->input('observacion'),
            $request->user(),
        );

        return new AsistenciaResource($asistencia->load(['estudiante:id,user_id', 'estudiante.user:id,nombres,apellidos,ci', 'historial.usuario:id,nombres,apellidos']));
    }

    public function historial(Asistencia $asistencia): AnonymousResourceCollection
    {
        $this->authorize('view', $asistencia);

        return AsistenciaHistorialResource::collection(
            $asistencia->historial()->select(['id', 'asistencia_id', 'usuario_id', 'estado_anterior', 'estado_nuevo', 'observacion', 'created_at'])->with('usuario:id,nombres,apellidos')->orderByDesc('created_at')->get()
        );
    }
}
