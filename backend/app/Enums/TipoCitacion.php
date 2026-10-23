<?php

namespace App\Enums;

enum TipoCitacion: string
{
    case PADRE = 'padre';
    case PROFESOR_INFORMATIVA = 'profesor_informativa';
    case PROFESOR_EXTRAORDINARIA = 'profesor_extraordinaria';

    public function label(): string
    {
        return match ($this) {
            self::PADRE => 'Citación a Padre de Familia',
            self::PROFESOR_INFORMATIVA => 'Comunicado a Profesor',
            self::PROFESOR_EXTRAORDINARIA => 'Citación Extraordinaria a Profesor',
        };
    }
}
