<?php

namespace App\Models;

use App\Enums\EstadoGenerico;
use App\Enums\NivelEducativo;
use App\Enums\Turno;
use App\Models\Concerns\BelongsToUnidadEducativa;
use Database\Factories\AulaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Aula extends Model
{
    /** @use HasFactory<AulaFactory> */
    use BelongsToUnidadEducativa, HasFactory, SoftDeletes;

    protected $fillable = [
        'gestion_id',
        'unidad_educativa_id',
        'grado',
        'paralelo',
        'turno',
        'nivel',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'turno' => Turno::class,
            'nivel' => NivelEducativo::class,
            'estado' => EstadoGenerico::class,
        ];
    }

    public function gestion(): BelongsTo
    {
        return $this->belongsTo(Gestion::class);
    }

    public function unidadEducativa(): BelongsTo
    {
        return $this->belongsTo(UnidadEducativa::class);
    }

    public function aulaMaterias(): HasMany
    {
        return $this->hasMany(AulaMateria::class);
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class);
    }

    public function nombre(): string
    {
        return "{$this->grado} {$this->paralelo}";
    }
}
