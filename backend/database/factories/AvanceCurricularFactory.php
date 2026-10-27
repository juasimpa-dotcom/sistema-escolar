<?php

namespace Database\Factories;

use App\Models\AulaMateria;
use App\Models\AvanceCurricular;
use App\Models\Periodo;
use App\Models\UnidadEducativa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AvanceCurricular>
 */
class AvanceCurricularFactory extends Factory
{
    public function definition(): array
    {
        $programadas = fake()->numberBetween(10, 40);

        return [
            'unidad_educativa_id' => UnidadEducativa::factory(),
            'aula_materia_id' => AulaMateria::factory(),
            'periodo_id' => Periodo::factory(),
            'planificacion_entregada' => fake()->boolean(),
            'fecha_entrega' => fake()->dateTimeBetween('-2 months', 'now')->format('Y-m-d'),
            'clases_programadas' => $programadas,
            'clases_dictadas' => fake()->numberBetween(0, $programadas),
        ];
    }

    public function configure(): void
    {
        $this->afterCreating(function ($avance) {
            $unidadAula = $avance->aulaMateria?->aula?->unidad_educativa_id;

            if ($unidadAula && $unidadAula !== $avance->unidad_educativa_id) {
                $avance->update(['unidad_educativa_id' => $unidadAula]);
            }
        });
    }
}
