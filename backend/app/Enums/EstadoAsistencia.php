<?php

namespace App\Enums;

enum EstadoAsistencia: string
{
    case PRESENTE = 'presente';
    case FALTA = 'falta';
    case LICENCIA = 'licencia';
    case ATRASO = 'atraso';

    public function label(): string
    {
        return match ($this) {
            self::PRESENTE => 'Presente',
            self::FALTA => 'Falta',
            self::LICENCIA => 'Licencia',
            self::ATRASO => 'Atraso',
        };
    }
}
