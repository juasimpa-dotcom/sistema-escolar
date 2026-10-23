<?php

namespace App\Policies;

use App\Enums\Rol;
use App\Models\Actividad;
use App\Models\AulaMateria;
use App\Models\User;

class ActividadPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->rol, [Rol::SUPERADMIN, Rol::DIRECTOR, Rol::ADMINISTRATIVO, Rol::PROFESOR], true);
    }

    /**
     * Crear una actividad dentro de un aula-materia.
     */
    public function create(User $user, AulaMateria $aulaMateria): bool
    {
        if (! in_array($user->rol, [Rol::SUPERADMIN, Rol::DIRECTOR, Rol::ADMINISTRATIVO], true)) {
            return false;
        }

        return $user->rol === Rol::SUPERADMIN
            || $this->mismaUnidad($user, $aulaMateria->aula?->unidad_educativa_id);
    }

    public function view(User $user, Actividad $actividad): bool
    {
        $unidadId = $actividad->aulaMateria?->aula?->unidad_educativa_id;
        $profesorId = $actividad->aulaMateria?->profesor_id;

        if ($user->rol === Rol::SUPERADMIN) {
            return true;
        }

        if ($user->rol === Rol::PROFESOR) {
            return $profesorId === $user->id;
        }

        return $this->mismaUnidad($user, $unidadId);
    }

    public function update(User $user, Actividad $actividad): bool
    {
        if (! in_array($user->rol, [Rol::SUPERADMIN, Rol::DIRECTOR, Rol::ADMINISTRATIVO], true)) {
            return false;
        }

        return $user->rol === Rol::SUPERADMIN
            || $this->mismaUnidad($user, $actividad->aulaMateria?->aula?->unidad_educativa_id);
    }

    public function delete(User $user, Actividad $actividad): bool
    {
        return $this->update($user, $actividad);
    }

    private function mismaUnidad(User $user, ?int $unidadId): bool
    {
        return $user->unidad_educativa_id !== null && $user->unidad_educativa_id === $unidadId;
    }
}
