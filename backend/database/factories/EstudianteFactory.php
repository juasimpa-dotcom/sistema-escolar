<?php

namespace Database\Factories;

use App\Enums\EstadoUsuario;
use App\Models\Estudiante;
use App\Models\UnidadEducativa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Estudiante>
 */
class EstudianteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->estudiante(),
            'unidad_educativa_id' => UnidadEducativa::factory(),
            'fecha_nacimiento' => fake()->dateTimeBetween('-18 years', '-6 years')->format('Y-m-d'),
            'estado' => EstadoUsuario::ACTIVO,
        ];
    }

    public function configure(): void
    {
        $this->afterCreating(function ($estudiante) {
            if ($estudiante->user && ! $estudiante->user->unidad_educativa_id) {
                $estudiante->user->update([
                    'unidad_educativa_id' => $estudiante->unidad_educativa_id,
                ]);
            }
        });
    }
}
