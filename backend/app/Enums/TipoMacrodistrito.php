<?php

namespace App\Enums;

enum TipoMacrodistrito: string
{
    case URBANO = 'urbano';
    case RURAL = 'rural';

    public function label(): string
    {
        return match ($this) {
            self::URBANO => 'Urbano',
            self::RURAL => 'Rural',
        };
    }
}
