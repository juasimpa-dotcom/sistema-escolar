<?php

namespace App\Services;

use App\Enums\EstadoInscripcion;
use App\Models\Actividad;
use App\Models\Inscripcion;
use App\Models\Nota;
use App\Models\NotaHistorial;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NotaService
{
    /**
     * Registra (o actualiza la nota inicial de) un estudiante en una actividad.
     * Si ya existe una nota no modificada, se sobrescribe mientras no se haya
     * usado la única corrección permitida.
     */
    public function registrar(Actividad $actividad, int $estudianteId, float $valor, User $profesor): Nota
    {
        $this->assertEstudianteInscrito($actividad, $estudianteId);
        $this->assertRango($actividad, $valor);

        return DB::transaction(function () use ($actividad, $estudianteId, $valor, $profesor) {
            $nota = Nota::where('actividad_id', $actividad->id)
                ->where('estudiante_id', $estudianteId)
                ->first();

            if ($nota) {
                if ($nota->fue_modificada) {
                    throw ValidationException::withMessages([
                        'nota' => ['La nota ya fue corregida una vez; no se puede modificar de nuevo.'],
                    ]);
                }

                $nota->update([
                    'nota' => $valor,
                    'registrado_por' => $nota->registrado_por ?? $profesor->id,
                ]);

                return $nota->refresh();
            }

            return Nota::create([
                'actividad_id' => $actividad->id,
                'estudiante_id' => $estudianteId,
                'nota' => $valor,
                'fue_modificada' => false,
                'registrado_por' => $profesor->id,
            ]);
        });
    }

    /**
     * Corrige una nota una única vez. Exige observación y guarda historial.
     */
    public function modificar(Nota $nota, float $nuevoValor, string $observacion, User $profesor): Nota
    {
        if ($nota->fue_modificada) {
            throw ValidationException::withMessages([
                'nota' => ['Esta nota ya fue corregida una vez; no se permite otra modificación.'],
            ]);
        }

        $this->assertRango($nota->actividad, $nuevoValor);

        if (trim($observacion) === '') {
            throw ValidationException::withMessages([
                'observacion' => ['La observación es obligatoria al corregir una nota.'],
            ]);
        }

        return DB::transaction(function () use ($nota, $nuevoValor, $observacion, $profesor) {
            NotaHistorial::create([
                'nota_id' => $nota->id,
                'nota_anterior' => $nota->nota,
                'nota_nueva' => $nuevoValor,
                'observacion' => $observacion,
                'usuario_id' => $profesor->id,
            ]);

            $nota->update([
                'nota' => $nuevoValor,
                'observacion' => $observacion,
                'fue_modificada' => true,
                'modificado_por' => $profesor->id,
            ]);

            return $nota->refresh();
        });
    }

    private function assertRango(Actividad $actividad, float $valor): void
    {
        if ($valor < 0 || $valor > (float) $actividad->puntaje_maximo) {
            throw ValidationException::withMessages([
                'nota' => ["La nota debe estar entre 0 y {$actividad->puntaje_maximo}."],
            ]);
        }
    }

    private function assertEstudianteInscrito(Actividad $actividad, int $estudianteId): void
    {
        $aulaId = $actividad->aulaMateria?->aula_id;
        $gestionId = $actividad->aulaMateria?->aula?->gestion_id;

        $inscrito = Inscripcion::where('estudiante_id', $estudianteId)
            ->where('aula_id', $aulaId)
            ->where('gestion_id', $gestionId)
            ->where('estado', EstadoInscripcion::INSCRITO)
            ->exists();

        if (! $inscrito) {
            throw ValidationException::withMessages([
                'estudiante_id' => ['El estudiante no está inscrito en el aula de esta actividad.'],
            ]);
        }
    }
}
