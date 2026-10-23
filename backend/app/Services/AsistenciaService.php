<?php

namespace App\Services;

use App\Enums\EstadoAsistencia;
use App\Enums\EstadoInscripcion;
use App\Enums\Rol;
use App\Models\Asistencia;
use App\Models\AsistenciaHistorial;
use App\Models\AulaMateria;
use App\Models\Inscripcion;
use App\Models\Licencia;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AsistenciaService
{
    /**
     * Toma de asistencia diaria (bulk) para una materia-aula.
     *
     * Si el estudiante tiene una licencia aprobada para la fecha, su estado
     * se fuerza a "licencia" (no se acepta el valor enviado por el profesor).
     */
    public function registrarToma(AulaMateria $aulaMateria, string $fecha, array $registros, User $profesor): int
    {
        $aulaId = $aulaMateria->aula_id;
        $gestionId = $aulaMateria->aula?->gestion_id;

        $this->assertFechaPermitida($fecha);

        return DB::transaction(function () use ($aulaMateria, $fecha, $registros, $profesor, $aulaId, $gestionId) {
            $inasistencias = [];

            foreach ($registros as $indice => $fila) {
                $estudianteId = (int) (is_array($fila) ? $fila['estudiante_id'] : $fila->estudiante_id);
                $valor = is_array($fila) ? $fila['estado'] : $fila->estado;
                $observacion = is_array($fila) ? ($fila['observacion'] ?? null) : ($fila->observacion ?? null);

                $this->assertInscrito($estudianteId, $aulaId, $gestionId, "registros.$indice.estudiante_id");

                $estado = EstadoAsistencia::from($valor);

                if (Licencia::hayAprobadaPara($estudianteId, $fecha)) {
                    $estado = EstadoAsistencia::LICENCIA;
                }

                $existente = Asistencia::query()
                    ->where('aula_materia_id', $aulaMateria->id)
                    ->where('estudiante_id', $estudianteId)
                    ->where('fecha', $fecha)
                    ->first();

                if ($existente) {
                    if ($existente->estado === EstadoAsistencia::LICENCIA && $estado !== EstadoAsistencia::LICENCIA) {
                        continue;
                    }

                    $existente->update([
                        'estado' => $estado,
                        'observacion' => $observacion,
                        'registrado_por' => $existente->registrado_por ?? $profesor->id,
                    ]);

                    continue;
                }

                Asistencia::create([
                    'aula_materia_id' => $aulaMateria->id,
                    'estudiante_id' => $estudianteId,
                    'fecha' => $fecha,
                    'estado' => $estado,
                    'observacion' => $observacion,
                    'registrado_por' => $profesor->id,
                ]);

                $inasistencias[] = $estudianteId;
            }

            return count($inasistencias);
        });
    }

    /**
     * Modificación de una asistencia puntual. Registra auditoría siempre.
     * El profesor no puede modificar asistencias en estado licencia.
     */
    public function modificar(Asistencia $asistencia, EstadoAsistencia $nuevoEstado, ?string $observacion, User $usuario): Asistencia
    {
        if ($usuario->rol === Rol::PROFESOR && $asistencia->estado === EstadoAsistencia::LICENCIA) {
            throw ValidationException::withMessages([
                'estado' => ['No se puede modificar una asistencia en estado licencia.'],
            ]);
        }

        if (Licencia::hayAprobadaPara($asistencia->estudiante_id, $asistencia->fecha->toDateString())
            && $nuevoEstado !== EstadoAsistencia::LICENCIA
            && $usuario->rol === Rol::PROFESOR) {
            throw ValidationException::withMessages([
                'estado' => ['El estudiante tiene una licencia aprobada para esa fecha.'],
            ]);
        }

        $anterior = $asistencia->estado;

        if ($anterior === $nuevoEstado && $observacion === $asistencia->observacion) {
            return $asistencia;
        }

        return DB::transaction(function () use ($asistencia, $anterior, $nuevoEstado, $observacion, $usuario) {
            AsistenciaHistorial::create([
                'asistencia_id' => $asistencia->id,
                'estado_anterior' => $anterior,
                'estado_nuevo' => $nuevoEstado,
                'observacion' => $observacion,
                'usuario_id' => $usuario->id,
            ]);

            $asistencia->update([
                'estado' => $nuevoEstado,
                'observacion' => $observacion,
                'modificado_por' => $usuario->id,
            ]);

            return $asistencia->refresh();
        });
    }

    private function assertFechaPermitida(string $fecha): void
    {
        if ($fecha > now()->toDateString()) {
            throw ValidationException::withMessages([
                'fecha' => ['No se puede registrar asistencia de una fecha futura.'],
            ]);
        }
    }

    private function assertInscrito(int $estudianteId, int $aulaId, ?int $gestionId, string $campo): void
    {
        $inscrito = Inscripcion::withoutGlobalScopes()
            ->where('estudiante_id', $estudianteId)
            ->where('aula_id', $aulaId)
            ->where('gestion_id', $gestionId)
            ->where('estado', EstadoInscripcion::INSCRITO->value)
            ->exists();

        if (! $inscrito) {
            throw ValidationException::withMessages([
                $campo => ['El estudiante no está inscrito en el aula de esta materia.'],
            ]);
        }
    }
}
