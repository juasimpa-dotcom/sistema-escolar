<?php

namespace App\Enums;

enum Rol: string
{
    case SUPERADMIN = 'superadmin';
    case DIRECTOR = 'director';
    case ADMINISTRATIVO = 'administrativo';
    case PROFESOR = 'profesor';
    case PADRE = 'padre';
    case ESTUDIANTE = 'estudiante';

    public function label(): string
    {
        return match ($this) {
            self::SUPERADMIN => 'Superadmin',
            self::DIRECTOR => 'Director',
            self::ADMINISTRATIVO => 'Administrativo',
            self::PROFESOR => 'Profesor',
            self::PADRE => 'Padre de Familia',
            self::ESTUDIANTE => 'Estudiante',
        };
    }
}
