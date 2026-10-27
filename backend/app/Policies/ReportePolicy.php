<?php

namespace App\Policies;

use App\Enums\Rol;
use App\Models\User;
use App\Support\Reporte;

/**
 * Autorización del módulo de reportes: solo Superadmin y Director,
 * y cada rol accede únicamente a los reportes de su alcance.
 */
class ReportePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->rol, [Rol::SUPERADMIN, Rol::DIRECTOR], true);
    }

    public function ver(User $user, Reporte $reporte): bool
    {
        return $reporte->permiteRol($user->rol);
    }

    public function exportar(User $user, Reporte $reporte): bool
    {
        return $this->ver($user, $reporte);
    }
}
