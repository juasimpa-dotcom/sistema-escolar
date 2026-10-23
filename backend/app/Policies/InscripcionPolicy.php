<?php

namespace App\Policies;

use App\Enums\Rol;
use App\Models\Inscripcion;
use App\Models\User;

class InscripcionPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->rol, [Rol::SUPERADMIN, Rol::DIRECTOR, Rol::ADMINISTRATIVO, Rol::PROFESOR], true);
    }

    public function view(User $user, Inscripcion $inscripcion): bool
    {
        return $user->rol === Rol::SUPERADMIN || $this->mismaUnidad($user, $inscripcion->unidad_educativa_id);
    }

    public function create(User $user): bool
    {
        return in_array($user->rol, [Rol::DIRECTOR, Rol::ADMINISTRATIVO], true);
    }

    public function delete(User $user, Inscripcion $inscripcion): bool
    {
        return $this->create($user) && $this->mismaUnidad($user, $inscripcion->unidad_educativa_id);
    }

    private function mismaUnidad(User $user, ?int $unidadId): bool
    {
        return $user->unidad_educativa_id !== null && $user->unidad_educativa_id === $unidadId;
    }
}
