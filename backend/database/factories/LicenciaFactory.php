<?php

namespace Database\Factories;

use App\Enums\EstadoLicencia;
use App\Models\Estudiante;
use App\Models\Licencia;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Licencia>
 */
class LicenciaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'estudiante_id' => Estudiante::factory(),
            'padre_id' => User::factory()->padre(),
            'fecha_inicio' => fake()->dateTimeBetween('today', '+1 week')->format('Y-m-d'),
            'fecha_fin' => fake()->dateTimeBetween('+1 week', '+2 weeks')->format('Y-m-d'),
            'motivo' => fake()->sentence(8),
            'comprobante_path' => null,
            'estado' => EstadoLicencia::PENDIENTE,
        ];
    }

    public function aprobada(): static
    {
        return $this->state(fn () => [
            'estado' => EstadoLicencia::APROBADA,
            'resuelto_por' => User::factory()->administrativo(),
            'fecha_resolucion' => now(),
        ]);
    }

    public function rechazada(): static
    {
        return $this->state(fn () => [
            'estado' => EstadoLicencia::RECHAZADA,
            'resuelto_por' => User::factory()->administrativo(),
            'fecha_resolucion' => now(),
            'motivo_rechazo' => 'Motivo no justificado',
        ]);
    }
}
