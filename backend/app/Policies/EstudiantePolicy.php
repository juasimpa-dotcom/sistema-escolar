<?php

namespace App\Policies;

use App\Enums\Rol;
use App\Models\Estudiante;
use App\Models\User;

class EstudiantePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->rol, [Rol::SUPERADMIN, Rol::DIRECTOR, Rol::ADMINISTRATIVO, Rol::PROFESOR], true);
    }

    public function view(User $user, Estudiante $estudiante): bool
    {
        if ($user->rol === Rol::SUPERADMIN) {
            return true;
        }

        if ($user->rol === Rol::PADRE) {
            return $user->hijos()->whereKey($estudiante->id)->exists();
        }

        if ($user->rol === Rol::ESTUDIANTE) {
            return $estudiante->user_id === $user->id;
        }

        return $this->mismaUnidad($user, $estudiante);
    }

    public function create(User $user): bool
    {
        return in_array($user->rol, [Rol::DIRECTOR, Rol::ADMINISTRATIVO], true);
    }

    public function update(User $user, Estudiante $estudiante): bool
    {
        if (! in_array($user->rol, [Rol::DIRECTOR, Rol::ADMINISTRATIVO], true)) {
            return false;
        }

        return $this->mismaUnidad($user, $estudiante);
    }

    public function darDeBaja(User $user, Estudiante $estudiante): bool
    {
        if ($user->rol !== Rol::DIRECTOR) {
            return false;
        }

        return $this->mismaUnidad($user, $estudiante);
    }

    private function mismaUnidad(User $user, Estudiante $estudiante): bool
    {
        return $user->unidad_educativa_id !== null
            && $user->unidad_educativa_id === $estudiante->unidad_educativa_id;
    }
}
