<?php

namespace Database\Factories;

use App\Models\DistritoMunicipal;
use App\Models\Macrodistrito;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DistritoMunicipal>
 */
class DistritoMunicipalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'macrodistrito_id' => Macrodistrito::factory(),
            'numero' => fake()->unique()->numberBetween(1, 24),
            'nombre' => 'Distrito '.fake()->unique()->numberBetween(1, 24),
        ];
    }
}
