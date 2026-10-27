<?php

namespace Database\Factories;

use App\Enums\EstadoPlaza;
use App\Enums\NivelEducativo;
use App\Models\Materia;
use App\Models\PlazaDocente;
use App\Models\UnidadEducativa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlazaDocente>
 */
class PlazaDocenteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'unidad_educativa_id' => UnidadEducativa::factory(),
            'materia_id' => Materia::factory(),
            'nivel' => fake()->randomElement([
                NivelEducativo::INICIAL,
                NivelEducativo::PRIMARIA,
                NivelEducativo::SECUNDARIA,
            ]),
            'estado' => EstadoPlaza::VACANTE,
            'profesor_id' => null,
        ];
    }

    public function ocupada(): static
    {
        return $this->state(fn () => [
            'estado' => EstadoPlaza::OCUPADA,
            'profesor_id' => User::factory()->profesor(),
        ]);
    }

    public function configure(): void
    {
        $this->afterCreating(function ($plaza) {
            if ($plaza->materia && $plaza->materia->unidad_educativa_id !== $plaza->unidad_educativa_id) {
                $plaza->update(['unidad_educativa_id' => $plaza->materia->unidad_educativa_id]);
            }
        });
    }
}
