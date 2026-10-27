<?php

namespace App\Support;

use App\Enums\Rol;

/**
 * Descriptor inmutable de un reporte del módulo (config/reportes.php).
 */
final class Reporte
{
    /**
     * @param  array<int, string>  $filtros
     * @param  array<int, string>  $roles
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $titulo,
        public readonly string $descripcion,
        public readonly array $filtros,
        public readonly array $roles,
    ) {}

    /**
     * @return array<string, self>
     */
    public static function todos(): array
    {
        $catalogo = [];

        foreach (config('reportes', []) as $slug => $def) {
            $catalogo[$slug] = new self(
                (string) $slug,
                (string) $def['titulo'],
                (string) ($def['descripcion'] ?? ''),
                array_values($def['filtros'] ?? []),
                array_values($def['roles'] ?? []),
            );
        }

        return $catalogo;
    }

    public static function buscar(string $slug): ?self
    {
        return self::todos()[$slug] ?? null;
    }

    /**
     * @return array<int, self>
     */
    public static function paraRol(Rol $rol): array
    {
        return array_values(array_filter(
            self::todos(),
            fn (self $reporte) => $reporte->permiteRol($rol)
        ));
    }

    public function permiteRol(Rol $rol): bool
    {
        return in_array($rol->value, $this->roles, true);
    }
}
