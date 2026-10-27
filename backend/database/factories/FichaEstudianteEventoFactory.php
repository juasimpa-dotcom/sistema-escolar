<?php

namespace Database\Factories;

use App\Enums\TipoFichaEvento;
use App\Models\Estudiante;
use App\Models\FichaEstudianteEvento;
use App\Models\UnidadEducativa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FichaEstudianteEvento>
 */
class FichaEstudianteEventoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'unidad_educativa_id' => UnidadEducativa::factory(),
            'estudiante_id' => Estudiante::factory(),
            'tipo' => fake()->randomElement([
                TipoFichaEvento::ACADEMICO,
                TipoFichaEvento::DISCIPLINARIO,
                TipoFichaEvento::SALUD,
            ]),
            'fecha' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'descripcion' => fake()->sentence(12),
            'registrado_por' => null,
        ];
    }
}
