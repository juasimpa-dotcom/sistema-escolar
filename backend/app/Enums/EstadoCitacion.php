<?php

namespace App\Enums;

enum EstadoCitacion: string
{
    case ENVIADA = 'enviada';
    case VISTA = 'vista';

    public function label(): string
    {
        return match ($this) {
            self::ENVIADA => 'Enviada',
            self::VISTA => 'Vista',
        };
    }
}
