<?php

namespace App\Policies;

use App\Enums\Rol;
use App\Models\Asistencia;
use App\Models\AulaMateria;
use App\Models\User;

class AsistenciaPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->rol, [Rol::SUPERADMIN, Rol::DIRECTOR, Rol::ADMINISTRATIVO, Rol::PROFESOR], true);
    }

    public function view(User $user, Asistencia $asistencia): bool
    {
        if ($user->rol === Rol::SUPERADMIN) {
            return true;
        }

        if ($user->rol === Rol::PROFESOR) {
            return $asistencia->aulaMateria?->profesor_id === $user->id;
        }

        return $this->mismaUnidad($user, $asistencia->unidad_educativa_id);
    }

    /**
     * Tomar asistencia de una materia-aula: solo su profesor titular.
     */
    public function registrar(User $user, AulaMateria $aulaMateria): bool
    {
        if ($user->rol === Rol::SUPERADMIN) {
            return true;
        }

        if ($user->rol !== Rol::PROFESOR) {
            return false;
        }

        return $aulaMateria->profesor_id === $user->id;
    }

    /**
     * Modificar una asistencia existente: Director/Administrativo de la unidad
     * (con auditoría) o el profesor titular salvo si está en estado licencia.
     */
    public function update(User $user, Asistencia $asistencia): bool
    {
        if ($user->rol === Rol::SUPERADMIN) {
            return true;
        }

        if (in_array($user->rol, [Rol::DIRECTOR, Rol::ADMINISTRATIVO], true)) {
            return $this->mismaUnidad($user, $asistencia->unidad_educativa_id);
        }

        if ($user->rol === Rol::PROFESOR) {
            return $asistencia->aulaMateria?->profesor_id === $user->id;
        }

        return false;
    }

    private function mismaUnidad(User $user, ?int $unidadId): bool
    {
        return $user->unidad_educativa_id !== null && $user->unidad_educativa_id === $unidadId;
    }
}
