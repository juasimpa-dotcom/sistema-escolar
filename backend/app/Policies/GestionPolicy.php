<?php

namespace App\Policies;

use App\Enums\Rol;
use App\Models\Gestion;
use App\Models\User;

class GestionPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->rol, [Rol::SUPERADMIN, Rol::DIRECTOR, Rol::ADMINISTRATIVO, Rol::PROFESOR], true);
    }

    public function view(User $user, Gestion $gestion): bool
    {
        return $user->rol === Rol::SUPERADMIN || $this->mismaUnidad($user, $gestion->unidad_educativa_id);
    }

    public function create(User $user): bool
    {
        return in_array($user->rol, [Rol::DIRECTOR, Rol::ADMINISTRATIVO], true);
    }

    public function update(User $user, Gestion $gestion): bool
    {
        return $this->create($user) && $this->mismaUnidad($user, $gestion->unidad_educativa_id);
    }

    public function delete(User $user, Gestion $gestion): bool
    {
        return $this->update($user, $gestion);
    }

    private function mismaUnidad(User $user, ?int $unidadId): bool
    {
        return $user->unidad_educativa_id !== null && $user->unidad_educativa_id === $unidadId;
    }
}
