<?php

namespace App\Enums;

enum EstadoUnidad: string
{
    case ACTIVA = 'activa';
    case INACTIVA = 'inactiva';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVA => 'Activa',
            self::INACTIVA => 'Inactiva',
        };
    }
}
