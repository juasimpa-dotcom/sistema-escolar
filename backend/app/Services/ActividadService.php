<?php

namespace App\Services;

use App\Models\Actividad;
use App\Models\AulaMateria;
use Illuminate\Validation\ValidationException;

class ActividadService
{
    public function crear(AulaMateria $aulaMateria, array $datos): Actividad
    {
        $this->validarPuntajes($datos);

        return Actividad::create([
            'aula_materia_id' => $aulaMateria->id,
            'titulo' => $datos['titulo'],
            'descripcion' => $datos['descripcion'] ?? null,
            'fecha_cumplimiento' => $datos['fecha_cumplimiento'],
            'fecha_limite_notas' => $datos['fecha_limite_notas'] ?? null,
            'puntaje_maximo' => $datos['puntaje_maximo'],
        ]);
    }

    public function actualizar(Actividad $actividad, array $datos): Actividad
    {
        $this->validarPuntajes($datos, $actividad);

        if ($actividad->notas()->exists()) {
            $soloFechas = ['fecha_cumplimiento', 'fecha_limite_notas', 'descripcion'];
            $intentoCambio = collect(['titulo', 'puntaje_maximo'])
                ->contains(fn ($campo) => isset($datos[$campo]) && (string) $datos[$campo] !== (string) $actividad->{$campo});

            if ($intentoCambio) {
                throw ValidationException::withMessages([
                    'titulo' => ['No se puede cambiar el título ni el puntaje máximo de una actividad con notas registradas.'],
                ]);
            }
        }

        $actividad->update([
            'titulo' => $datos['titulo'] ?? $actividad->titulo,
            'descripcion' => array_key_exists('descripcion', $datos) ? $datos['descripcion'] : $actividad->descripcion,
            'fecha_cumplimiento' => $datos['fecha_cumplimiento'] ?? $actividad->fecha_cumplimiento,
            'fecha_limite_notas' => array_key_exists('fecha_limite_notas', $datos) ? $datos['fecha_limite_notas'] : $actividad->fecha_limite_notas,
            'puntaje_maximo' => $datos['puntaje_maximo'] ?? $actividad->puntaje_maximo,
        ]);

        return $actividad->refresh();
    }

    public function eliminar(Actividad $actividad): void
    {
        if ($actividad->notas()->exists()) {
            throw ValidationException::withMessages([
                'actividad' => ['No se puede eliminar una actividad con notas registradas.'],
            ]);
        }

        $actividad->delete();
    }

    private function validarPuntajes(array $datos, ?Actividad $actividad = null): void
    {
        $maximo = $datos['puntaje_maximo'] ?? $actividad?->puntaje_maximo;

        if ($maximo !== null && (float) $maximo <= 0) {
            throw ValidationException::withMessages([
                'puntaje_maximo' => ['El puntaje máximo debe ser mayor a cero.'],
            ]);
        }

        $cumplimiento = $datos['fecha_cumplimiento'] ?? $actividad?->fecha_cumplimiento?->toDateString();
        $limite = array_key_exists('fecha_limite_notas', $datos)
            ? $datos['fecha_limite_notas']
            : $actividad?->fecha_limite_notas?->toDateString();

        if ($cumplimiento && $limite && $limite < $cumplimiento) {
            throw ValidationException::withMessages([
                'fecha_limite_notas' => ['La fecha límite de notas no puede ser anterior a la fecha de cumplimiento.'],
            ]);
        }
    }
}
