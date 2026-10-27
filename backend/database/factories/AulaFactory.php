<?php

namespace Database\Factories;

use App\Enums\EstadoGenerico;
use App\Enums\NivelEducativo;
use App\Enums\Turno;
use App\Models\Aula;
use App\Models\Gestion;
use App\Models\UnidadEducativa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Aula>
 */
class AulaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'gestion_id' => Gestion::factory(),
            'unidad_educativa_id' => UnidadEducativa::factory(),
            'grado' => fake()->randomElement(['1ro', '2do', '3ro', '4to', '5to', '6to']),
            'paralelo' => fake()->randomElement(['A', 'B', 'C']),
            'turno' => fake()->randomElement([Turno::MANANA, Turno::TARDE]),
            'nivel' => fake()->randomElement([
                NivelEducativo::INICIAL,
                NivelEducativo::PRIMARIA,
                NivelEducativo::SECUNDARIA,
            ]),
            'estado' => EstadoGenerico::ACTIVO,
        ];
    }

    public function configure(): void
    {
        $this->afterCreating(function ($aula) {
            if ($aula->gestion && $aula->gestion->unidad_educativa_id !== $aula->unidad_educativa_id) {
                $aula->update(['unidad_educativa_id' => $aula->gestion->unidad_educativa_id]);
            }
        });
    }
}
