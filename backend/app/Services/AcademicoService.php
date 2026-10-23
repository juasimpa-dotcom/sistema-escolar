<?php

namespace App\Services;

use App\Enums\EstadoInscripcion;
use App\Enums\Rol;
use App\Models\Aula;
use App\Models\AulaMateria;
use App\Models\Estudiante;
use App\Models\Inscripcion;
use App\Models\Materia;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AcademicoService
{
    /**
     * Asigna (o reasigna) una materia a un aula con su profesor.
     */
    public function asignarMateriaAAula(Aula $aula, int $materiaId, int $profesorId): AulaMateria
    {
        $this->assertProfesorValido($aula, $profesorId);
        $this->assertMateriaDeLaUnidad($aula, $materiaId);

        return AulaMateria::updateOrCreate(
            ['aula_id' => $aula->id, 'materia_id' => $materiaId],
            ['profesor_id' => $profesorId]
        );
    }

    public function quitarMateriaDeAula(Aula $aula, AulaMateria $aulaMateria): void
    {
        if ($aulaMateria->aula_id !== $aula->id) {
            throw ValidationException::withMessages([
                'materia' => ['La materia no pertenece a este aula.'],
            ]);
        }

        $aulaMateria->delete();
    }

    /**
     * Inscribe a un estudiante en un aula (gestion derivada del aula).
     */
    public function inscribir(Aula $aula, int $estudianteId): Inscripcion
    {
        $estudiante = Estudiante::findOrFail($estudianteId);

        if ($estudiante->unidad_educativa_id !== $aula->unidad_educativa_id) {
            throw ValidationException::withMessages([
                'estudiante_id' => ['El estudiante pertenece a otra unidad educativa.'],
            ]);
        }

        $yaInscrito = Inscripcion::where('estudiante_id', $estudianteId)
            ->where('gestion_id', $aula->gestion_id)
            ->where('estado', EstadoInscripcion::INSCRITO)
            ->exists();

        if ($yaInscrito) {
            throw ValidationException::withMessages([
                'estudiante_id' => ['El estudiante ya está inscrito en esta gestión.'],
            ]);
        }

        return Inscripcion::updateOrCreate(
            ['estudiante_id' => $estudianteId, 'gestion_id' => $aula->gestion_id],
            [
                'aula_id' => $aula->id,
                'fecha_inscripcion' => now()->toDateString(),
                'estado' => EstadoInscripcion::INSCRITO,
                'fecha_baja' => null,
                'motivo_baja' => null,
            ]
        );
    }

    public function retirar(Inscripcion $inscripcion, string $motivo, string $fecha): Inscripcion
    {
        $inscripcion->update([
            'estado' => EstadoInscripcion::RETIRADO,
            'motivo_baja' => $motivo,
            'fecha_baja' => $fecha,
        ]);

        return $inscripcion->refresh();
    }

    private function assertProfesorValido(Aula $aula, int $profesorId): void
    {
        $profesor = User::find($profesorId);

        if (! $profesor || $profesor->rol !== Rol::PROFESOR) {
            throw ValidationException::withMessages([
                'profesor_id' => ['El usuario seleccionado no es un profesor.'],
            ]);
        }

        if ($profesor->unidad_educativa_id !== $aula->unidad_educativa_id) {
            throw ValidationException::withMessages([
                'profesor_id' => ['El profesor pertenece a otra unidad educativa.'],
            ]);
        }
    }

    private function assertMateriaDeLaUnidad(Aula $aula, int $materiaId): void
    {
        $pertenece = Materia::whereKey($materiaId)
            ->where('unidad_educativa_id', $aula->unidad_educativa_id)
            ->exists();

        if (! $pertenece) {
            throw ValidationException::withMessages([
                'materia_id' => ['La materia no pertenece a la unidad educativa del aula.'],
            ]);
        }
    }
}
