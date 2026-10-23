<?php

namespace Database\Factories;

use App\Enums\DiaSemana;
use App\Models\AulaMateria;
use App\Models\Horario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Horario>
 */
class HorarioFactory extends Factory
{
    public function definition(): array
    {
        $horaInicio = fake()->randomElement(['08:00', '08:45', '10:00', '14:00', '15:00']);

        return [
            'aula_materia_id' => AulaMateria::factory(),
            'dia' => fake()->randomElement([
                DiaSemana::LUNES,
                DiaSemana::MARTES,
                DiaSemana::MIERCOLES,
                DiaSemana::JUEVES,
                DiaSemana::VIERNES,
            ]),
            'hora_inicio' => $horaInicio,
            'hora_fin' => date('H:i', strtotime($horaInicio) + 45 * 60),
        ];
    }
}
