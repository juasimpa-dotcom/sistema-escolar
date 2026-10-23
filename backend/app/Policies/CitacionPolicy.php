<?php

namespace App\Policies;

use App\Enums\EstadoCitacion;
use App\Enums\Rol;
use App\Models\Citacion;
use App\Models\User;

class CitacionPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->rol, [Rol::SUPERADMIN, Rol::DIRECTOR, Rol::ADMINISTRATIVO, Rol::PROFESOR, Rol::PADRE], true);
    }

    public function view(User $user, Citacion $citacion): bool
    {
        if ($user->rol === Rol::SUPERADMIN) {
            return true;
        }

        if ($user->id === $citacion->emisor_id || $user->id === $citacion->destinatario_id) {
            return true;
        }

        if ($user->rol === Rol::PADRE && $citacion->estudiante_id !== null) {
            return $user->hijos()->whereKey($citacion->estudiante_id)->exists();
        }

        return false;
    }

    /**
     * Solo Director y Administrativo emiten citaciones/comunicados.
     */
    public function create(User $user): bool
    {
        return in_array($user->rol, [Rol::SUPERADMIN, Rol::DIRECTOR, Rol::ADMINISTRATIVO], true);
    }

    /**
     * El destinatario marca como vista su propia citación.
     */
    public function marcarVista(User $user, Citacion $citacion): bool
    {
        return $user->id === $citacion->destinatario_id;
    }

    /**
     * El emisor puede anular una citación que aún no fue vista.
     */
    public function delete(User $user, Citacion $citacion): bool
    {
        if ($citacion->estado === EstadoCitacion::VISTA) {
            return false;
        }

        if ($user->rol === Rol::SUPERADMIN) {
            return true;
        }

        return $user->id === $citacion->emisor_id;
    }
}
