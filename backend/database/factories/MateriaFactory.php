<?php

namespace Database\Factories;

use App\Enums\EstadoGenerico;
use App\Models\Materia;
use App\Models\UnidadEducativa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Materia>
 */
class MateriaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'unidad_educativa_id' => UnidadEducativa::factory(),
            'nombre' => fake()->unique()->randomElement([
                'Matemáticas',
                'Lenguaje y Literatura',
                'Ciencias Naturales',
                'Historia',
                'Geografía',
                'Inglés',
                'Educación Física',
                'Computación',
            ]),
            'estado' => EstadoGenerico::ACTIVO,
        ];
    }
}
