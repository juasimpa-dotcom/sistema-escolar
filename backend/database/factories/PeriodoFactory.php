<?php

namespace Database\Factories;

use App\Enums\TipoPeriodo;
use App\Models\Gestion;
use App\Models\Periodo;
use App\Models\UnidadEducativa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Periodo>
 */
class PeriodoFactory extends Factory
{
    public function definition(): array
    {
        $inicio = fake()->dateTimeBetween('-6 months', 'now');

        return [
            'unidad_educativa_id' => UnidadEducativa::factory(),
            'gestion_id' => Gestion::factory(),
            'nombre' => 'Periodo '.fake()->unique()->numberBetween(1, 99999),
            'tipo' => fake()->randomElement([
                TipoPeriodo::BIMESTRE,
                TipoPeriodo::TRIMESTRE,
                TipoPeriodo::SEMESTRE,
            ]),
            'fecha_inicio' => $inicio->format('Y-m-d'),
            'fecha_fin' => (clone $inicio)->modify('+2 months')->format('Y-m-d'),
        ];
    }
}
