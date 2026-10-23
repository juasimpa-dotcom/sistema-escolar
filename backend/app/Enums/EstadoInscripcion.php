<?php

namespace App\Enums;

enum EstadoInscripcion: string
{
    case INSCRITO = 'inscrito';
    case RETIRADO = 'retirado';

    public function label(): string
    {
        return match ($this) {
            self::INSCRITO => 'Inscrito',
            self::RETIRADO => 'Retirado',
        };
    }
}
