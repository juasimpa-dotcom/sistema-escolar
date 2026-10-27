<?php

namespace App\Enums;

enum TipoFichaEvento: string
{
    case ACADEMICO = 'academico';
    case DISCIPLINARIO = 'disciplinario';
    case SALUD = 'salud';

    public function label(): string
    {
        return match ($this) {
            self::ACADEMICO => 'Académico',
            self::DISCIPLINARIO => 'Disciplinario',
            self::SALUD => 'Salud',
        };
    }
}
