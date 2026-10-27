<?php

namespace Database\Factories;

use App\Models\Municipio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Municipio>
 */
class MunicipioFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->city(),
        ];
    }
}
