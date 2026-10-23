<?php

namespace App\Enums;

enum Turno: string
{
    case MANANA = 'manana';
    case TARDE = 'tarde';
    case NOCHE = 'noche';

    public function label(): string
    {
        return match ($this) {
            self::MANANA => 'Mañana',
            self::TARDE => 'Tarde',
            self::NOCHE => 'Noche',
        };
    }
}
