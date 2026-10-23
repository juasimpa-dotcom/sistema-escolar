<?php

namespace App\Policies;

use App\Enums\EstadoLicencia;
use App\Enums\Rol;
use App\Models\Estudiante;
use App\Models\Licencia;
use App\Models\User;

class LicenciaPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->rol, [Rol::SUPERADMIN, Rol::DIRECTOR, Rol::ADMINISTRATIVO, Rol::PADRE], true);
    }

    public function view(User $user, Licencia $licencia): bool
    {
        if ($user->rol === Rol::SUPERADMIN) {
            return true;
        }

        if ($user->rol === Rol::PADRE) {
            return $licencia->padre_id === $user->id
                || $user->hijos()->whereKey($licencia->estudiante_id)->exists();
        }

        return $this->mismaUnidad($user, $licencia->unidad_educativa_id);
    }

    /**
     * El padre solo puede solicitar licencia para sus hijos.
     */
    public function create(User $user, Estudiante $estudiante): bool
    {
        if ($user->rol !== Rol::PADRE) {
            return false;
        }

        return $user->hijos()->whereKey($estudiante->id)->exists();
    }

    /**
     * Aprobar o rechazar: solo Director (o Superadmin).
     */
    public function resolver(User $user, Licencia $licencia): bool
    {
        if ($licencia->estado !== EstadoLicencia::PENDIENTE) {
            return false;
        }

        if ($user->rol === Rol::SUPERADMIN) {
            return true;
        }

        return $user->rol === Rol::DIRECTOR && $this->mismaUnidad($user, $licencia->unidad_educativa_id);
    }

    /**
     * El padre puede anular su solicitud mientras siga pendiente.
     */
    public function delete(User $user, Licencia $licencia): bool
    {
        if ($licencia->estado !== EstadoLicencia::PENDIENTE) {
            return false;
        }

        return $user->rol === Rol::PADRE && $licencia->padre_id === $user->id;
    }

    private function mismaUnidad(User $user, ?int $unidadId): bool
    {
        return $user->unidad_educativa_id !== null && $user->unidad_educativa_id === $unidadId;
    }
}
