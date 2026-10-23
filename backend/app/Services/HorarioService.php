<?php

namespace App\Services;

use App\Models\AulaMateria;
use App\Models\Horario;
use Illuminate\Validation\ValidationException;

class HorarioService
{
    /**
     * Registra un bloque de horario, validando que:
     *  - La hora de fin sea mayor a la de inicio.
     *  - El aula no tenga otro bloque solapado el mismo día.
     *  - El profesor no tenga otra clase solapada el mismo día (en cualquier aula).
     */
    public function registrar(AulaMateria $aulaMateria, string $dia, string $horaInicio, string $horaFin): Horario
    {
        $this->assertRangoHoras($horaInicio, $horaFin);

        // Choque del aula
        $this->assertSinChoque(
            Horario::query()
                ->where('dia', $dia)
                ->whereHas('aulaMateria', fn ($q) => $q->where('aula_id', $aulaMateria->aula_id)),
            $horaInicio,
            $horaFin,
            'Ya existe una materia en el aula que se cruza en ese horario.'
        );

        // Choque del profesor (en cualquier aula de la unidad)
        $this->assertSinChoque(
            Horario::query()
                ->where('dia', $dia)
                ->whereHas('aulaMateria', fn ($q) => $q->where('profesor_id', $aulaMateria->profesor_id)),
            $horaInicio,
            $horaFin,
            'El profesor ya tiene otra clase asignada en ese horario.'
        );

        return Horario::create([
            'aula_materia_id' => $aulaMateria->id,
            'dia' => $dia,
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
        ]);
    }

    public function actualizar(Horario $horario, string $dia, string $horaInicio, string $horaFin): Horario
    {
        $this->assertRangoHoras($horaInicio, $horaFin);
        $aulaMateria = $horario->aulaMateria;

        $this->assertSinChoque(
            Horario::query()
                ->where('dia', $dia)
                ->where('id', '!=', $horario->id)
                ->whereHas('aulaMateria', fn ($q) => $q->where('aula_id', $aulaMateria->aula_id)),
            $horaInicio,
            $horaFin,
            'Ya existe una materia en el aula que se cruza en ese horario.'
        );

        $this->assertSinChoque(
            Horario::query()
                ->where('dia', $dia)
                ->where('id', '!=', $horario->id)
                ->whereHas('aulaMateria', fn ($q) => $q->where('profesor_id', $aulaMateria->profesor_id)),
            $horaInicio,
            $horaFin,
            'El profesor ya tiene otra clase asignada en ese horario.'
        );

        $horario->update([
            'dia' => $dia,
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
        ]);

        return $horario->refresh();
    }

    public function eliminar(Horario $horario): void
    {
        $horario->delete();
    }

    private function assertRangoHoras(string $horaInicio, string $horaFin): void
    {
        if (strtotime($horaFin) <= strtotime($horaInicio)) {
            throw ValidationException::withMessages([
                'hora_fin' => ['La hora de fin debe ser mayor a la hora de inicio.'],
            ]);
        }
    }

    /**
     * Detecta solapamiento: inicio existente < fin nuevo Y fin existente > inicio nuevo.
     */
    private function assertSinChoque($query, string $horaInicio, string $horaFin, string $mensaje): void
    {
        $choque = (clone $query)
            ->where('hora_inicio', '<', $horaFin)
            ->where('hora_fin', '>', $horaInicio)
            ->exists();

        if ($choque) {
            throw ValidationException::withMessages([
                'horario' => [$mensaje],
            ]);
        }
    }
}
