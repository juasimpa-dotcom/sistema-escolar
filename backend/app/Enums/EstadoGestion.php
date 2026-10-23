<?php

namespace App\Enums;

enum EstadoGestion: string
{
    case ACTIVA = 'activa';
    case CERRADA = 'cerrada';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVA => 'Activa',
            self::CERRADA => 'Cerrada',
        };
    }
}
