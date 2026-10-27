<?php

namespace App\Enums;

enum ResultadoInscripcion: string
{
    case EN_CURSO = 'en_curso';
    case PROMOVIDO = 'promovido';
    case REPROBADO = 'reprobado';
    case RETIRADO = 'retirado';
    case ABANDONO = 'abandono';

    public function label(): string
    {
        return match ($this) {
            self::EN_CURSO => 'En curso',
            self::PROMOVIDO => 'Promovido',
            self::REPROBADO => 'Reprobado',
            self::RETIRADO => 'Retirado',
            self::ABANDONO => 'Abandono',
        };
    }
}
