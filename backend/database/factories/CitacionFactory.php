<?php

namespace Database\Factories;

use App\Enums\EstadoCitacion;
use App\Enums\TipoCitacion;
use App\Models\Citacion;
use App\Models\Estudiante;
use App\Models\UnidadEducativa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Citacion>
 */
class CitacionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'unidad_educativa_id' => UnidadEducativa::factory(),
            'emisor_id' => User::factory()->director(),
            'destinatario_id' => User::factory()->padre(),
            'estudiante_id' => fake()->boolean(60) ? Estudiante::factory() : null,
            'tipo' => TipoCitacion::PADRE,
            'asunto' => fake()->sentence(4),
            'mensaje' => fake()->paragraph(2),
            'fecha_hora' => fake()->dateTimeBetween('+1 day', '+2 weeks'),
            'lugar' => fake()->boolean(70) ? 'Oficina de dirección' : null,
            'estado' => EstadoCitacion::ENVIADA,
        ];
    }

    public function vista(): static
    {
        return $this->state(fn () => [
            'estado' => EstadoCitacion::VISTA,
            'leida_en' => now(),
        ]);
    }
}
