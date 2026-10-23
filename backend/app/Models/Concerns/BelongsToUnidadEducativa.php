<?php

namespace App\Models\Concerns;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Aísla automáticamente los datos por unidad educativa.
 *
 * - El Superadmin ve todas las unidades.
 * - Cualquier otro rol solo ve registros de su propia unidad.
 * - Sin contexto de tenant (consola/seed/factories) no se filtra.
 */
trait BelongsToUnidadEducativa
{
    public static function bootBelongsToUnidadEducativa(): void
    {
        static::addGlobalScope('unidad_educativa', function (Builder $builder) {
            if (TenantContext::esSuperadmin()) {
                return;
            }

            if (! TenantContext::tieneTenant()) {
                return;
            }

            $builder->where(
                $builder->getModel()->getTable().'.unidad_educativa_id',
                TenantContext::id()
            );
        });

        // Al crear, asigna automáticamente la unidad activa si no viene explícita.
        static::creating(function (Model $model) {
            $columna = 'unidad_educativa_id';

            if (! isset($model->{$columna}) && TenantContext::tieneTenant()) {
                $model->{$columna} = TenantContext::id();
            }
        });
    }

    public function scopeDeUnidad(Builder $query, int $unidadEducativaId): Builder
    {
        return $query->where($this->getTable().'.unidad_educativa_id', $unidadEducativaId);
    }
}
