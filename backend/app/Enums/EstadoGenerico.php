<?php

namespace App\Enums;

enum EstadoGenerico: string
{
    case ACTIVO = 'activo';
    case INACTIVO = 'inactivo';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVO => 'Activo',
            self::INACTIVO => 'Inactivo',
        };
    }
}
