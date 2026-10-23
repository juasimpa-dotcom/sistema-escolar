<?php

namespace Database\Factories;

use App\Models\Actividad;
use App\Models\Estudiante;
use App\Models\Nota;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Nota>
 */
class NotaFactory extends Factory
{
    public function definition(): array
    {
        $actividad = Actividad::factory()->create();
        $puntaje = (float) $actividad->puntaje_maximo;

        return [
            'actividad_id' => $actividad->id,
            'estudiante_id' => Estudiante::factory(),
            'nota' => fake()->randomFloat(2, $puntaje * 0.3, $puntaje),
            'observacion' => fake()->boolean(20) ? fake()->sentence(5) : null,
            'fue_modificada' => false,
            'registrado_por' => User::factory()->profesor(),
        ];
    }
}
