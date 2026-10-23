<?php

namespace Database\Factories;

use App\Enums\EstadoUsuario;
use App\Enums\Rol;
use App\Models\UnidadEducativa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password = null;

    public function definition(): array
    {
        $nombres = fake()->firstName();
        $apellidos = fake()->lastName().' '.fake()->lastName();
        $ci = (string) fake()->unique()->numberBetween(1000000, 99999999);

        return [
            'unidad_educativa_id' => UnidadEducativa::factory(),
            'rol' => Rol::PROFESOR,
            'ci' => $ci,
            'usuario' => $ci,
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'email' => fake()->unique()->safeEmail(),
            'telefono' => fake()->phoneNumber(),
            'password' => static::$password ??= Hash::make('password'),
            'debe_cambiar_password' => true,
            'estado' => EstadoUsuario::ACTIVO,
        ];
    }

    public function superadmin(): static
    {
        return $this->state(fn () => [
            'unidad_educativa_id' => null,
            'rol' => Rol::SUPERADMIN,
        ]);
    }

    public function director(): static
    {
        return $this->state(fn () => ['rol' => Rol::DIRECTOR]);
    }

    public function administrativo(): static
    {
        return $this->state(fn () => ['rol' => Rol::ADMINISTRATIVO]);
    }

    public function profesor(): static
    {
        return $this->state(fn () => ['rol' => Rol::PROFESOR]);
    }

    public function padre(): static
    {
        return $this->state(fn () => ['rol' => Rol::PADRE]);
    }

    public function estudiante(): static
    {
        return $this->state(fn () => ['rol' => Rol::ESTUDIANTE]);
    }

    public function password(string $password): static
    {
        return $this->state(fn () => [
            'password' => Hash::make($password),
        ]);
    }
}
