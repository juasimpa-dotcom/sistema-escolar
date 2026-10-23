<?php

namespace App\Policies;

use App\Enums\Rol;
use App\Models\Aula;
use App\Models\User;

class AulaPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->rol, [Rol::SUPERADMIN, Rol::DIRECTOR, Rol::ADMINISTRATIVO, Rol::PROFESOR], true);
    }

    public function view(User $user, Aula $aula): bool
    {
        return $user->rol === Rol::SUPERADMIN || $this->mismaUnidad($user, $aula->unidad_educativa_id);
    }

    public function create(User $user): bool
    {
        return in_array($user->rol, [Rol::DIRECTOR, Rol::ADMINISTRATIVO], true);
    }

    public function update(User $user, Aula $aula): bool
    {
        return $this->create($user) && $this->mismaUnidad($user, $aula->unidad_educativa_id);
    }

    public function delete(User $user, Aula $aula): bool
    {
        return $this->update($user, $aula);
    }

    /**
     * Gestionar materias/profesor del aula.
     */
    public function gestionarMaterias(User $user, Aula $aula): bool
    {
        return $this->update($user, $aula);
    }

    /**
     * Gestionar el horario del aula.
     */
    public function gestionarHorario(User $user, Aula $aula): bool
    {
        return $this->update($user, $aula);
    }

    private function mismaUnidad(User $user, ?int $unidadId): bool
    {
        return $user->unidad_educativa_id !== null && $user->unidad_educativa_id === $unidadId;
    }
}
