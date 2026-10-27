<?php

namespace App\Enums;

enum TipoUnidad: string
{
    case FISCAL = 'fiscal';
    case CONVENIO = 'convenio';
    case PRIVADA = 'privada';

    public function label(): string
    {
        return match ($this) {
            self::FISCAL => 'Fiscal',
            self::CONVENIO => 'Convenio',
            self::PRIVADA => 'Privada',
        };
    }
}
