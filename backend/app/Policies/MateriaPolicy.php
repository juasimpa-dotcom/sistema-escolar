<?php

namespace App\Policies;

use App\Enums\Rol;
use App\Models\Materia;
use App\Models\User;

class MateriaPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->rol, [Rol::SUPERADMIN, Rol::DIRECTOR, Rol::ADMINISTRATIVO, Rol::PROFESOR], true);
    }

    public function view(User $user, Materia $materia): bool
    {
        return $user->rol === Rol::SUPERADMIN || $this->mismaUnidad($user, $materia->unidad_educativa_id);
    }

    public function create(User $user): bool
    {
        return in_array($user->rol, [Rol::DIRECTOR, Rol::ADMINISTRATIVO], true);
    }

    public function update(User $user, Materia $materia): bool
    {
        return $this->create($user) && $this->mismaUnidad($user, $materia->unidad_educativa_id);
    }

    public function delete(User $user, Materia $materia): bool
    {
        return $this->update($user, $materia);
    }

    private function mismaUnidad(User $user, ?int $unidadId): bool
    {
        return $user->unidad_educativa_id !== null && $user->unidad_educativa_id === $unidadId;
    }
}
