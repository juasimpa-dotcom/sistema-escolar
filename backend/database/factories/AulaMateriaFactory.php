<?php

namespace Database\Factories;

use App\Models\Aula;
use App\Models\AulaMateria;
use App\Models\Materia;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AulaMateria>
 */
class AulaMateriaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'aula_id' => Aula::factory(),
            'materia_id' => Materia::factory(),
            'profesor_id' => User::factory()->profesor(),
        ];
    }
}
