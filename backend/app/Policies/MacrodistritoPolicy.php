<?php

namespace App\Policies;

use App\Enums\Rol;
use App\Models\Macrodistrito;
use App\Models\User;

class MacrodistritoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->rol === Rol::SUPERADMIN;
    }

    public function view(User $user, Macrodistrito $macrodistrito): bool
    {
        return $user->rol === Rol::SUPERADMIN;
    }

    public function create(User $user): bool
    {
        return $user->rol === Rol::SUPERADMIN;
    }

    public function update(User $user, Macrodistrito $macrodistrito): bool
    {
        return $user->rol === Rol::SUPERADMIN;
    }

    public function delete(User $user, Macrodistrito $macrodistrito): bool
    {
        return $user->rol === Rol::SUPERADMIN;
    }
}
