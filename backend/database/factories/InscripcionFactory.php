<?php

namespace Database\Factories;

use App\Enums\EstadoInscripcion;
use App\Enums\ResultadoInscripcion;
use App\Models\Aula;
use App\Models\Estudiante;
use App\Models\Gestion;
use App\Models\Inscripcion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inscripcion>
 */
class InscripcionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'estudiante_id' => Estudiante::factory(),
            'aula_id' => Aula::factory(),
            'gestion_id' => Gestion::factory(),
            'fecha_inscripcion' => fake()->date(),
            'estado' => EstadoInscripcion::INSCRITO,
            'resultado_final' => null,
            'promedio_final' => null,
            'fecha_resultado' => null,
        ];
    }

    public function conResultado(ResultadoInscripcion $resultado, ?float $promedio = null): static
    {
        return $this->state(fn () => [
            'resultado_final' => $resultado,
            'promedio_final' => $promedio ?? fake()->randomFloat(2, 0, 100),
            'fecha_resultado' => fake()->date(),
        ]);
    }
}
