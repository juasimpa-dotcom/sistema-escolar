<?php

namespace App\Models;

use Database\Factories\ActividadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Actividad extends Model
{
    /** @use HasFactory<ActividadFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'actividades';

    protected $fillable = [
        'aula_materia_id',
        'titulo',
        'descripcion',
        'fecha_cumplimiento',
        'fecha_limite_notas',
        'puntaje_maximo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_cumplimiento' => 'date',
            'fecha_limite_notas' => 'date',
            'puntaje_maximo' => 'decimal:2',
        ];
    }

    public function aulaMateria(): BelongsTo
    {
        return $this->belongsTo(AulaMateria::class);
    }

    public function notas(): HasMany
    {
        return $this->hasMany(Nota::class);
    }
}
