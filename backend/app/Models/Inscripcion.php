<?php

namespace App\Models;

use App\Enums\EstadoInscripcion;
use App\Support\TenantContext;
use Database\Factories\InscripcionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inscripcion extends Model
{
    /** @use HasFactory<InscripcionFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'inscripciones';

    protected $fillable = [
        'estudiante_id',
        'aula_id',
        'gestion_id',
        'fecha_inscripcion',
        'estado',
        'fecha_baja',
        'motivo_baja',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inscripcion' => 'date',
            'estado' => EstadoInscripcion::class,
            'fecha_baja' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('unidad_educativa', function (Builder $builder) {
            if (TenantContext::esSuperadmin() || ! TenantContext::tieneTenant()) {
                return;
            }

            $builder->whereHas('aula', function (Builder $q) {
                $q->where('aulas.unidad_educativa_id', TenantContext::id());
            });
        });
    }

    public function getUnidadEducativaIdAttribute(): ?int
    {
        return $this->aula?->unidad_educativa_id;
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function aula(): BelongsTo
    {
        return $this->belongsTo(Aula::class);
    }

    public function gestion(): BelongsTo
    {
        return $this->belongsTo(Gestion::class);
    }
}
