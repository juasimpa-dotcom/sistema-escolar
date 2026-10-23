<?php

namespace App\Services;

use App\Enums\EstadoAsistencia;
use App\Enums\EstadoInscripcion;
use App\Models\Actividad;
use App\Models\Asistencia;
use App\Models\AulaMateria;
use App\Models\Citacion;
use App\Models\Estudiante;
use App\Models\Inscripcion;
use App\Models\Licencia;
use App\Models\Nota;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Reúne, en una sola estructura, toda la información que un padre o un
 * estudiante necesita ver sobre un estudiante (portal de familia / estudiante).
 *
 * No aplica reglas de autorización: el llamador (PortalController) ya validó
 * el acceso mediante la EstudiantePolicy. Solo arma la vista de datos.
 */
class PortalService
{
    /**
     * @return array<string, mixed>
     */
    public function resumen(Estudiante $estudiante): array
    {
        $estudiante->loadMissing('user');

        $inscripcion = $this->inscripcionActual($estudiante);

        $aula = $inscripcion?->aula;

        $aulaMaterias = $aula
            ? AulaMateria::query()
                ->where('aula_id', $aula->id)
                ->with(['materia', 'profesor'])
                ->get()
            : new EloquentCollection;

        $materias = $this->materiasConNotas($estudiante, $aulaMaterias);

        return [
            'estudiante' => [
                'id' => $estudiante->id,
                'ci' => $estudiante->user?->ci,
                'nombre_completo' => $estudiante->user?->nombreCompleto(),
                'fecha_nacimiento' => $estudiante->fecha_nacimiento?->toDateString(),
            ],
            'aula' => $aula ? [
                'id' => $aula->id,
                'nombre' => $aula->nombre(),
                'grado' => $aula->grado,
                'paralelo' => $aula->paralelo,
                'turno' => $aula->turno?->value,
                'turno_label' => $aula->turno?->label(),
                'gestion' => $aula->gestion?->anio,
            ] : null,
            'materias' => $materias,
            'pendientes' => $this->pendientes($estudiante),
            'asistencia' => $this->asistencia($estudiante),
            'citaciones' => $this->citaciones($estudiante),
            'licencias' => $this->licencias($estudiante),
        ];
    }

    private function inscripcionActual(Estudiante $estudiante): ?Inscripcion
    {
        return Inscripcion::query()
            ->where('estudiante_id', $estudiante->id)
            ->where('estado', EstadoInscripcion::INSCRITO)
            ->with(['aula.gestion'])
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Materias del aula con sus actividades, la nota obtenida por el estudiante
     * y el profesor que la imparte.
     */
    private function materiasConNotas(Estudiante $estudiante, EloquentCollection $aulaMaterias): Collection
    {
        if ($aulaMaterias->isEmpty()) {
            return collect();
        }

        $aulaMateriaIds = $aulaMaterias->pluck('id');

        $actividades = Actividad::query()
            ->whereIn('aula_materia_id', $aulaMateriaIds)
            ->orderBy('fecha_cumplimiento')
            ->get();

        $notasPorActividad = Nota::query()
            ->where('estudiante_id', $estudiante->id)
            ->whereIn('actividad_id', $actividades->pluck('id'))
            ->get()
            ->keyBy('actividad_id');

        $actividadesPorMateria = $actividades->groupBy('aula_materia_id');

        return $aulaMaterias->map(function (AulaMateria $am) use ($actividadesPorMateria, $notasPorActividad) {
            $actividades = $actividadesPorMateria->get($am->id, collect());

            $lista = $actividades->map(function (Actividad $actividad) use ($notasPorActividad) {
                $nota = $notasPorActividad->get($actividad->id);

                return [
                    'actividad_id' => $actividad->id,
                    'titulo' => $actividad->titulo,
                    'descripcion' => $actividad->descripcion,
                    'fecha_cumplimiento' => $actividad->fecha_cumplimiento?->toDateString(),
                    'puntaje_maximo' => (float) $actividad->puntaje_maximo,
                    'nota' => $nota ? (float) $nota->nota : null,
                    'fue_modificada' => $nota?->fue_modificada ?? false,
                    'registrada' => $nota !== null,
                ];
            })->values();

            $conNota = $lista->where('registrada', true);

            return [
                'aula_materia_id' => $am->id,
                'materia' => $am->materia?->nombre,
                'profesor' => $am->profesor?->nombreCompleto(),
                'promedio' => $conNota->isNotEmpty()
                    ? round($conNota->avg('nota'), 2)
                    : null,
                'actividades' => $lista,
            ];
        })->values();
    }

    /**
     * Actividades aún sin nota para el estudiante (pendientes).
     */
    private function pendientes(Estudiante $estudiante): array
    {
        $aulaMaterias = AulaMateria::query()
            ->whereHas('aula.inscripciones', function ($q) use ($estudiante) {
                $q->where('estudiante_id', $estudiante->id)
                    ->where('estado', EstadoInscripcion::INSCRITO->value);
            })
            ->pluck('id');

        if ($aulaMaterias->isEmpty()) {
            return [];
        }

        $conNota = Nota::query()
            ->where('estudiante_id', $estudiante->id)
            ->pluck('actividad_id');

        return Actividad::query()
            ->whereIn('aula_materia_id', $aulaMaterias)
            ->whereNotIn('id', $conNota)
            ->with(['aulaMateria.materia'])
            ->orderBy('fecha_cumplimiento')
            ->get()
            ->map(fn (Actividad $a) => [
                'actividad_id' => $a->id,
                'titulo' => $a->titulo,
                'materia' => $a->aulaMateria?->materia?->nombre,
                'fecha_cumplimiento' => $a->fecha_cumplimiento?->toDateString(),
                'puntaje_maximo' => (float) $a->puntaje_maximo,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function asistencia(Estudiante $estudiante): array
    {
        $registros = Asistencia::query()
            ->where('estudiante_id', $estudiante->id)
            ->get();

        $porEstado = $registros->groupBy(fn (Asistencia $a) => $a->estado->value)
            ->map->count();

        return [
            'total' => $registros->count(),
            'presentes' => (int) ($porEstado[EstadoAsistencia::PRESENTE->value] ?? 0),
            'faltas' => (int) ($porEstado[EstadoAsistencia::FALTA->value] ?? 0),
            'atrasos' => (int) ($porEstado[EstadoAsistencia::ATRASO->value] ?? 0),
            'licencias' => (int) ($porEstado[EstadoAsistencia::LICENCIA->value] ?? 0),
            'detalle' => Asistencia::query()
                ->where('estudiante_id', $estudiante->id)
                ->with('aulaMateria.materia')
                ->orderByDesc('fecha')
                ->limit(50)
                ->get()
                ->map(fn (Asistencia $a) => [
                    'id' => $a->id,
                    'fecha' => $a->fecha?->toDateString(),
                    'estado' => $a->estado->value,
                    'estado_label' => $a->estado->label(),
                    'materia' => $a->aulaMateria?->materia?->nombre,
                    'observacion' => $a->observacion,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * Citaciones dirigidas a los padres/tutores del estudiante.
     */
    private function citaciones(Estudiante $estudiante): array
    {
        return Citacion::query()
            ->where('estudiante_id', $estudiante->id)
            ->with('emisor')
            ->orderByDesc('fecha_hora')
            ->get()
            ->map(fn (Citacion $c) => [
                'id' => $c->id,
                'tipo_label' => $c->tipo->label(),
                'asunto' => $c->asunto,
                'mensaje' => $c->mensaje,
                'fecha_hora' => $c->fecha_hora?->toDateTimeString(),
                'lugar' => $c->lugar,
                'estado' => $c->estado->value,
                'estado_label' => $c->estado->label(),
                'emisor' => $c->emisor?->nombreCompleto(),
            ])
            ->values()
            ->all();
    }

    /**
     * Licencias del estudiante.
     */
    private function licencias(Estudiante $estudiante): array
    {
        return Licencia::query()
            ->where('estudiante_id', $estudiante->id)
            ->orderByDesc('fecha_inicio')
            ->get()
            ->map(fn (Licencia $l) => [
                'id' => $l->id,
                'fecha_inicio' => $l->fecha_inicio?->toDateString(),
                'fecha_fin' => $l->fecha_fin?->toDateString(),
                'motivo' => $l->motivo,
                'estado' => $l->estado->value,
                'estado_label' => $l->estado->label(),
                'motivo_rechazo' => $l->motivo_rechazo,
            ])
            ->values()
            ->all();
    }
}
