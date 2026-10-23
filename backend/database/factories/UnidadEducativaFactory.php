<?php

namespace Database\Factories;

use App\Enums\EstadoUnidad;
use App\Models\UnidadEducativa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UnidadEducativa>
 */
class UnidadEducativaFactory extends Factory
{
    public function definition(): array
    {
        $nombre = fake()->unique()->company().' Unidad Educativa';

        return [
            'nombre' => $nombre,
            'codigo' => strtoupper(fake()->unique()->bothify('UE-####')),
            'direccion' => fake()->address(),
            'telefono' => fake()->phoneNumber(),
            'estado' => EstadoUnidad::ACTIVA,
        ];
    }

    public function inactiva(): static
    {
        return $this->state(fn () => [
            'estado' => EstadoUnidad::INACTIVA,
            'fecha_baja' => fake()->date(),
            'motivo_baja' => 'Cierre de operaciones',
        ]);
    }
}
