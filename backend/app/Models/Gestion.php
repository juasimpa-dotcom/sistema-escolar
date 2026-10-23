<?php

namespace App\Models;

use App\Enums\EstadoGestion;
use App\Models\Concerns\BelongsToUnidadEducativa;
use Database\Factories\GestionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gestion extends Model
{
    /** @use HasFactory<GestionFactory> */
    use BelongsToUnidadEducativa, HasFactory, SoftDeletes;

    protected $table = 'gestiones';

    protected $fillable = [
        'unidad_educativa_id',
        'anio',
        'fecha_inicio',
        'fecha_fin',
        'es_actual',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'anio' => 'integer',
            'es_actual' => 'boolean',
            'estado' => EstadoGestion::class,
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    public function unidadEducativa(): BelongsTo
    {
        return $this->belongsTo(UnidadEducativa::class);
    }

    public function aulas(): HasMany
    {
        return $this->hasMany(Aula::class);
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class);
    }
}
