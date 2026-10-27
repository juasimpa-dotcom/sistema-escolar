<?php

namespace App\Enums;

enum NivelEducativo: string
{
    case INICIAL = 'inicial';
    case PRIMARIA = 'primaria';
    case SECUNDARIA = 'secundaria';

    public function label(): string
    {
        return match ($this) {
            self::INICIAL => 'Inicial',
            self::PRIMARIA => 'Primaria',
            self::SECUNDARIA => 'Secundaria',
        };
    }
}
