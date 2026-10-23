<?php

namespace App\Policies;

use App\Enums\Rol;
use App\Models\UnidadEducativa;
use App\Models\User;

class UnidadEducativaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->rol === Rol::SUPERADMIN;
    }

    public function view(User $user, UnidadEducativa $unidadEducativa): bool
    {
        if ($user->rol === Rol::SUPERADMIN) {
            return true;
        }

        return $user->unidad_educativa_id === $unidadEducativa->id;
    }

    public function create(User $user): bool
    {
        return $user->rol === Rol::SUPERADMIN;
    }

    public function update(User $user, UnidadEducativa $unidadEducativa): bool
    {
        return $user->rol === Rol::SUPERADMIN;
    }

    public function delete(User $user, UnidadEducativa $unidadEducativa): bool
    {
        return $user->rol === Rol::SUPERADMIN;
    }
}
