<?php

namespace App\Support;

/**
 * Contexto del tenant activo (unidad educativa) durante la request.
 * El middleware SetTenant lo establece; el global scope lo consulta.
 */
class TenantContext
{
    protected static ?int $unidadEducativaId = null;

    protected static bool $superadmin = false;

    public static function set(?int $unidadEducativaId, bool $superadmin = false): void
    {
        static::$unidadEducativaId = $unidadEducativaId;
        static::$superadmin = $superadmin;
    }

    public static function id(): ?int
    {
        return static::$unidadEducativaId;
    }

    public static function esSuperadmin(): bool
    {
        return static::$superadmin;
    }

    public static function tieneTenant(): bool
    {
        return static::$unidadEducativaId !== null;
    }

    public static function limpiar(): void
    {
        static::$unidadEducativaId = null;
        static::$superadmin = false;
    }
}
