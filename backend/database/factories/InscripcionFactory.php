<?php

namespace Database\Factories;

use App\Enums\EstadoInscripcion;
use App\Models\Aula;
use App\Models\Estudiante;
use App\Models\Gestion;
use App\Models\Inscripcion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inscripcion>
 */
class InscripcionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'estudiante_id' => Estudiante::factory(),
            'aula_id' => Aula::factory(),
            'gestion_id' => Gestion::factory(),
            'fecha_inscripcion' => fake()->date(),
            'estado' => EstadoInscripcion::INSCRITO,
        ];
    }
}
