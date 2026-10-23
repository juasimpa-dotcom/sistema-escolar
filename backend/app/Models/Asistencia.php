<?php

namespace App\Models;

use App\Enums\EstadoAsistencia;
use App\Support\TenantContext;
use Database\Factories\AsistenciaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asistencia extends Model
{
    /** @use HasFactory<AsistenciaFactory> */
    use HasFactory;

    protected $fillable = [
        'aula_materia_id',
        'estudiante_id',
        'fecha',
        'estado',
        'observacion',
        'registrado_por',
        'modificado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'estado' => EstadoAsistencia::class,
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('unidad_educativa', function (Builder $builder) {
            if (TenantContext::esSuperadmin() || ! TenantContext::tieneTenant()) {
                return;
            }

            $builder->whereHas('aulaMateria.aula', function (Builder $q) {
                $q->where('aulas.unidad_educativa_id', TenantContext::id());
            });
        });
    }

    public function getUnidadEducativaIdAttribute(): ?int
    {
        return $this->aulaMateria?->aula?->unidad_educativa_id;
    }

    public function aulaMateria(): BelongsTo
    {
        return $this->belongsTo(AulaMateria::class);
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function modificadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'modificado_por');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(AsistenciaHistorial::class, 'asistencia_id');
    }
}
