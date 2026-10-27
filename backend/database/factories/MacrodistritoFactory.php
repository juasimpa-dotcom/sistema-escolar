<?php

namespace Database\Factories;

use App\Enums\TipoMacrodistrito;
use App\Models\Macrodistrito;
use App\Models\Municipio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Macrodistrito>
 */
class MacrodistritoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'municipio_id' => Municipio::factory(),
            'codigo' => 'MD'.fake()->unique()->numberBetween(1, 99),
            'nombre' => fake()->unique()->city(),
            'tipo' => fake()->randomElement([TipoMacrodistrito::URBANO, TipoMacrodistrito::RURAL]),
        ];
    }
}
