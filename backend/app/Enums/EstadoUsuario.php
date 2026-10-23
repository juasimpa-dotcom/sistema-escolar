<?php

namespace App\Enums;

enum EstadoUsuario: string
{
    case ACTIVO = 'activo';
    case BAJA = 'baja';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVO => 'Activo',
            self::BAJA => 'Baja',
        };
    }
}
