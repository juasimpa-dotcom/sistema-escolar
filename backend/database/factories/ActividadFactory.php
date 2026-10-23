<?php

namespace Database\Factories;

use App\Models\Actividad;
use App\Models\AulaMateria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Actividad>
 */
class ActividadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'aula_materia_id' => AulaMateria::factory(),
            'titulo' => fake()->randomElement([
                'Tarea 1',
                'Examen parcial',
                'Proyecto final',
                'Guía de ejercicios',
                'Participación en clase',
            ]),
            'descripcion' => fake()->sentence(10),
            'fecha_cumplimiento' => fake()->dateTimeBetween('-2 weeks', '+2 weeks')->format('Y-m-d'),
            'fecha_limite_notas' => fake()->dateTimeBetween('+1 week', '+3 weeks')->format('Y-m-d'),
            'puntaje_maximo' => fake()->randomElement([20, 50, 100]),
        ];
    }
}
