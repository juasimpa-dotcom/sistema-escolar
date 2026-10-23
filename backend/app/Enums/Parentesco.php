<?php

namespace App\Enums;

enum Parentesco: string
{
    case MADRE = 'madre';
    case PADRE = 'padre';
    case TUTOR = 'tutor';
    case OTRO = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::MADRE => 'Madre',
            self::PADRE => 'Padre',
            self::TUTOR => 'Tutor',
            self::OTRO => 'Otro',
        };
    }
}
