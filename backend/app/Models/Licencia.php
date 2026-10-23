<?php

namespace App\Models;

use App\Enums\EstadoLicencia;
use App\Support\TenantContext;
use Database\Factories\LicenciaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Licencia extends Model
{
    /** @use HasFactory<LicenciaFactory> */
    use HasFactory;

    protected $fillable = [
        'estudiante_id',
        'padre_id',
        'fecha_inicio',
        'fecha_fin',
        'motivo',
        'comprobante_path',
        'estado',
        'resuelto_por',
        'fecha_resolucion',
        'motivo_rechazo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'estado' => EstadoLicencia::class,
            'fecha_resolucion' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('unidad_educativa', function (Builder $builder) {
            if (TenantContext::esSuperadmin() || ! TenantContext::tieneTenant()) {
                return;
            }

            $builder->whereHas('estudiante', function (Builder $q) {
                $q->where('estudiantes.unidad_educativa_id', TenantContext::id());
            });
        });
    }

    public function getUnidadEducativaIdAttribute(): ?int
    {
        return $this->estudiante?->unidad_educativa_id;
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function padre(): BelongsTo
    {
        return $this->belongsTo(User::class, 'padre_id');
    }

    public function resueltoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resuelto_por');
    }

    /**
     * ¿Existe una licencia aprobada para el estudiante que cubra la fecha dada?
     */
    public static function hayAprobadaPara(int $estudianteId, string $fecha): bool
    {
        return static::withoutGlobalScopes()
            ->where('estudiante_id', $estudianteId)
            ->where('estado', EstadoLicencia::APROBADA->value)
            ->whereDate('fecha_inicio', '<=', $fecha)
            ->whereDate('fecha_fin', '>=', $fecha)
            ->exists();
    }

    /**
     * IDs de estudiantes (de un conjunto dado) con licencia aprobada que cubre
     * la fecha indicada. Una sola consulta para evitar N+1 en planillas.
     *
     * @param  array<int, int>  $estudianteIds
     * @return array<int, int>
     */
    public static function estudiantesConLicenciaEn(array $estudianteIds, string $fecha): array
    {
        if (empty($estudianteIds)) {
            return [];
        }

        return static::withoutGlobalScopes()
            ->whereIn('estudiante_id', $estudianteIds)
            ->where('estado', EstadoLicencia::APROBADA->value)
            ->whereDate('fecha_inicio', '<=', $fecha)
            ->whereDate('fecha_fin', '>=', $fecha)
            ->distinct()
            ->pluck('estudiante_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
