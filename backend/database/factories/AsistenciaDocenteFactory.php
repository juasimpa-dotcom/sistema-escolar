<?php

namespace Database\Factories;

use App\Enums\EstadoAsistencia;
use App\Models\AsistenciaDocente;
use App\Models\UnidadEducativa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AsistenciaDocente>
 */
class AsistenciaDocenteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'unidad_educativa_id' => UnidadEducativa::factory(),
            'profesor_id' => User::factory()->profesor(),
            'fecha' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'estado' => fake()->randomElement([
                EstadoAsistencia::PRESENTE,
                EstadoAsistencia::FALTA,
                EstadoAsistencia::LICENCIA,
                EstadoAsistencia::ATRASO,
            ]),
            'hora_ingreso' => fake()->time('H:i'),
            'observacion' => null,
            'registrado_por' => null,
        ];
    }
}
