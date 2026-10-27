<?php

namespace App\Enums;

enum Sexo: string
{
    case MASCULINO = 'M';
    case FEMENINO = 'F';

    public function label(): string
    {
        return match ($this) {
            self::MASCULINO => 'Masculino',
            self::FEMENINO => 'Femenino',
        };
    }
}
