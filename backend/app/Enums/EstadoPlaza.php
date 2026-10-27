<?php

namespace App\Enums;

enum EstadoPlaza: string
{
    case OCUPADA = 'ocupada';
    case VACANTE = 'vacante';

    public function label(): string
    {
        return match ($this) {
            self::OCUPADA => 'Ocupada',
            self::VACANTE => 'Vacante',
        };
    }
}
