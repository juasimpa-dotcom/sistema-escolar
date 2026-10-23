<?php

namespace App\Policies;

use App\Enums\Rol;
use App\Models\User;

class UserPolicy
{
    /**
     * Director y Administrativo gestionan personas de su unidad.
     * Superadmin gestiona directores de cualquier unidad.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->rol, [Rol::SUPERADMIN, Rol::DIRECTOR, Rol::ADMINISTRATIVO], true);
    }

    public function view(User $user, User $model): bool
    {
        if ($user->rol === Rol::SUPERADMIN) {
            return true;
        }

        if (! in_array($user->rol, [Rol::DIRECTOR, Rol::ADMINISTRATIVO], true)) {
            return false;
        }

        return $this->mismaUnidad($user, $model);
    }

    /**
     * El superadmin solo crea Directores. Director y Administrativo crean
     * profesores, administrativos, estudiantes y padres de su unidad.
     */
    public function create(User $user): bool
    {
        return in_array($user->rol, [Rol::SUPERADMIN, Rol::DIRECTOR, Rol::ADMINISTRATIVO], true);
    }

    public function update(User $user, User $model): bool
    {
        if ($user->rol === Rol::SUPERADMIN) {
            return $model->rol === Rol::DIRECTOR;
        }

        if (! in_array($user->rol, [Rol::DIRECTOR, Rol::ADMINISTRATIVO], true)) {
            return false;
        }

        return $this->mismaUnidad($user, $model)
            && in_array($model->rol, [Rol::PROFESOR, Rol::ADMINISTRATIVO, Rol::PADRE, Rol::ESTUDIANTE], true);
    }

    /**
     * Baja lógica. Director da de baja profesores, estudiantes y padres;
     * el superadmin da de baja directores.
     */
    public function darDeBaja(User $user, User $model): bool
    {
        if ($user->rol === Rol::SUPERADMIN) {
            return $model->rol === Rol::DIRECTOR;
        }

        if ($user->rol !== Rol::DIRECTOR) {
            return false;
        }

        return $this->mismaUnidad($user, $model)
            && in_array($model->rol, [Rol::PROFESOR, Rol::ESTUDIANTE, Rol::PADRE], true);
    }

    public function delete(User $user, User $model): bool
    {
        return $this->darDeBaja($user, $model);
    }

    private function mismaUnidad(User $user, User $model): bool
    {
        return $user->unidad_educativa_id !== null
            && $user->unidad_educativa_id === $model->unidad_educativa_id;
    }
}
