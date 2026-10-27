<?php

namespace App\Enums;

enum TipoPeriodo: string
{
    case BIMESTRE = 'bimestre';
    case TRIMESTRE = 'trimestre';
    case SEMESTRE = 'semestre';

    public function label(): string
    {
        return match ($this) {
            self::BIMESTRE => 'Bimestre',
            self::TRIMESTRE => 'Trimestre',
            self::SEMESTRE => 'Semestre',
        };
    }
}
