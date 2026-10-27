<?php

namespace App\Policies;

use App\Enums\Rol;
use App\Models\DistritoMunicipal;
use App\Models\User;

class DistritoMunicipalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->rol === Rol::SUPERADMIN;
    }

    public function view(User $user, DistritoMunicipal $distrito): bool
    {
        return $user->rol === Rol::SUPERADMIN;
    }

    public function create(User $user): bool
    {
        return $user->rol === Rol::SUPERADMIN;
    }

    public function update(User $user, DistritoMunicipal $distrito): bool
    {
        return $user->rol === Rol::SUPERADMIN;
    }

    public function delete(User $user, DistritoMunicipal $distrito): bool
    {
        return $user->rol === Rol::SUPERADMIN;
    }
}
