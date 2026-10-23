<?php

namespace Database\Factories;

use App\Enums\EstadoGestion;
use App\Models\Gestion;
use App\Models\UnidadEducativa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gestion>
 */
class GestionFactory extends Factory
{
    public function definition(): array
    {
        $anio = (int) fake()->unique()->year();

        return [
            'unidad_educativa_id' => UnidadEducativa::factory(),
            'anio' => $anio,
            'fecha_inicio' => sprintf('%d-02-01', $anio),
            'fecha_fin' => sprintf('%d-12-15', $anio),
            'es_actual' => false,
            'estado' => EstadoGestion::ACTIVA,
        ];
    }

    public function actual(): static
    {
        return $this->state(fn () => [
            'anio' => (int) now()->year,
            'fecha_inicio' => now()->year.'-02-01',
            'fecha_fin' => now()->year.'-12-15',
            'es_actual' => true,
            'estado' => EstadoGestion::ACTIVA,
        ]);
    }
}
