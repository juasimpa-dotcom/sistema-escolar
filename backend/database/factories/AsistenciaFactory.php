<?php

namespace Database\Factories;

use App\Enums\EstadoAsistencia;
use App\Models\Asistencia;
use App\Models\AulaMateria;
use App\Models\Estudiante;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asistencia>
 */
class AsistenciaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'aula_materia_id' => AulaMateria::factory(),
            'estudiante_id' => Estudiante::factory(),
            'fecha' => fake()->dateTimeBetween('-2 weeks', 'today')->format('Y-m-d'),
            'estado' => fake()->randomElement([
                EstadoAsistencia::PRESENTE,
                EstadoAsistencia::PRESENTE,
                EstadoAsistencia::PRESENTE,
                EstadoAsistencia::FALTA,
                EstadoAsistencia::ATRASO,
            ]),
            'registrado_por' => User::factory()->profesor(),
        ];
    }
}
