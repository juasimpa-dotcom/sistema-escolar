<?php

namespace App\Policies;

use App\Enums\Rol;
use App\Models\Actividad;
use App\Models\Nota;
use App\Models\User;

class NotaPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->rol, [Rol::SUPERADMIN, Rol::DIRECTOR, Rol::ADMINISTRATIVO, Rol::PROFESOR], true);
    }

    public function view(User $user, Nota $nota): bool
    {
        $actividad = $nota->actividad;

        if ($user->rol === Rol::SUPERADMIN) {
            return true;
        }

        if ($user->rol === Rol::PROFESOR) {
            return $actividad?->aulaMateria?->profesor_id === $user->id;
        }

        if ($user->rol === Rol::PADRE) {
            return $user->hijos()->whereKey($nota->estudiante_id)->exists();
        }

        if ($user->rol === Rol::ESTUDIANTE) {
            return $nota->estudiante?->user_id === $user->id;
        }

        return $this->mismaUnidad($user, $actividad?->aulaMateria?->aula?->unidad_educativa_id);
    }

    /**
     * Registrar una nota en la actividad: solo el profesor titular de esa materia-aula
     * (o el superadmin).
     */
    public function create(User $user, Actividad $actividad): bool
    {
        if ($user->rol === Rol::SUPERADMIN) {
            return true;
        }

        if ($user->rol !== Rol::PROFESOR) {
            return false;
        }

        return $actividad->aulaMateria?->profesor_id === $user->id;
    }

    /**
     * Corregir una nota: solo el profesor titular. La regla de "una sola vez"
     * se valida en el servicio.
     */
    public function update(User $user, Nota $nota): bool
    {
        if ($user->rol === Rol::SUPERADMIN) {
            return true;
        }

        if ($user->rol !== Rol::PROFESOR) {
            return false;
        }

        return $nota->actividad?->aulaMateria?->profesor_id === $user->id;
    }

    private function mismaUnidad(User $user, ?int $unidadId): bool
    {
        return $user->unidad_educativa_id !== null && $user->unidad_educativa_id === $unidadId;
    }
}
