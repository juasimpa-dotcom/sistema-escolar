<?php

namespace App\Services;

use App\Enums\EstadoUsuario;
use App\Enums\Rol;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PersonaService
{
    /**
     * Crea un usuario con credenciales iniciales (usuario = CI, password = CI).
     * El primer ingreso obliga a cambiar la contraseña.
     */
    public function crearUsuario(array $datos, Rol $rol, int $unidadEducativaId): User
    {
        $this->asegurarCiUnico($datos['ci']);

        return User::create([
            'unidad_educativa_id' => $unidadEducativaId,
            'rol' => $rol,
            'ci' => $datos['ci'],
            'usuario' => $datos['ci'],
            'nombres' => $datos['nombres'],
            'apellidos' => $datos['apellidos'],
            'email' => $datos['email'] ?? null,
            'telefono' => $datos['telefono'] ?? null,
            'password' => $datos['ci'],
            'debe_cambiar_password' => true,
            'estado' => EstadoUsuario::ACTIVO,
        ]);
    }

    /**
     * Crea una persona (profesor, administrativo o padre) en una unidad.
     */
    public function crearPersona(array $datos, Rol $rol, int $unidadEducativaId): User
    {
        return $this->crearUsuario($datos, $rol, $unidadEducativaId);
    }

    /**
     * Crea un estudiante y, opcionalmente, lo asocia a un padre existente
     * o crea uno nuevo (un padre puede tener varios hijos).
     *
     * Datos esperados:
     *  - estudiante: {ci, nombres, apellidos, fecha_nacimiento, email?, telefono?}
     *  - padre_existente_id?: int
     *  - padre_nuevo?: {ci, nombres, apellidos, email?, telefono?}
     *  - parentesco?: string
     */
    public function crearEstudiante(array $datos, int $unidadEducativaId): User
    {
        return DB::transaction(function () use ($datos, $unidadEducativaId) {
            $estudiante = $this->crearUsuario($datos['estudiante'], Rol::ESTUDIANTE, $unidadEducativaId);

            $estudiante->estudiante()->create([
                'unidad_educativa_id' => $unidadEducativaId,
                'fecha_nacimiento' => $datos['estudiante']['fecha_nacimiento'] ?? null,
                'estado' => EstadoUsuario::ACTIVO,
            ]);

            $padre = $this->resolverPadre($datos, $unidadEducativaId);

            if ($padre) {
                $this->asociarPadre($padre, $estudiante, $datos['parentesco'] ?? 'padre');
            }

            return $estudiante->load('estudiante.padres');
        });
    }

    public function asociarPadre(User $padre, User $estudiante, string $parentesco): void
    {
        if ($padre->rol !== Rol::PADRE) {
            throw ValidationException::withMessages([
                'padre_existente_id' => ['El usuario seleccionado no tiene rol de padre de familia.'],
            ]);
        }

        if ($padre->unidad_educativa_id !== $estudiante->unidad_educativa_id) {
            throw ValidationException::withMessages([
                'padre_existente_id' => ['El padre pertenece a otra unidad educativa.'],
            ]);
        }

        $estudiante->estudiante->padres()->syncWithoutDetaching([
            $padre->id => ['parentesco' => $parentesco],
        ]);
    }

    /**
     * Baja lógica de un usuario y, si corresponde, de su registro de estudiante.
     */
    public function darDeBaja(User $user, string $motivo, string $fecha): User
    {
        $user->update([
            'estado' => EstadoUsuario::BAJA,
            'motivo_baja' => $motivo,
            'fecha_baja' => $fecha,
        ]);

        if ($user->estudiante) {
            $user->estudiante->update([
                'estado' => EstadoUsuario::BAJA,
                'motivo_baja' => $motivo,
                'fecha_baja' => $fecha,
            ]);
        }

        return $user->refresh();
    }

    private function resolverPadre(array $datos, int $unidadEducativaId): ?User
    {
        if (! empty($datos['padre_existente_id'])) {
            $padre = User::find($datos['padre_existente_id']);

            if (! $padre) {
                throw ValidationException::withMessages([
                    'padre_existente_id' => ['El padre seleccionado no existe.'],
                ]);
            }

            return $padre;
        }

        if (! empty($datos['padre_nuevo'])) {
            return $this->crearUsuario($datos['padre_nuevo'], Rol::PADRE, $unidadEducativaId);
        }

        return null;
    }

    private function asegurarCiUnico(string $ci): void
    {
        if (User::withTrashed()->where('ci', $ci)->exists()) {
            throw ValidationException::withMessages([
                'ci' => ["El CI {$ci} ya está registrado."],
            ]);
        }
    }
}
