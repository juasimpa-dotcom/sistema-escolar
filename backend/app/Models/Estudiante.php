<?php

namespace App\Models;

use App\Enums\EstadoUsuario;
use App\Models\Concerns\BelongsToUnidadEducativa;
use Database\Factories\EstudianteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Estudiante extends Model
{
    /** @use HasFactory<EstudianteFactory> */
    use BelongsToUnidadEducativa, HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'unidad_educativa_id',
        'fecha_nacimiento',
        'estado',
        'fecha_baja',
        'motivo_baja',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'estado' => EstadoUsuario::class,
            'fecha_baja' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function unidadEducativa(): BelongsTo
    {
        return $this->belongsTo(UnidadEducativa::class);
    }

    public function padres(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'padre_estudiante', 'estudiante_id', 'padre_id')
            ->withPivot('parentesco')
            ->withTimestamps();
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class);
    }

    public function notas(): HasMany
    {
        return $this->hasMany(Nota::class);
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class);
    }

    public function licencias(): HasMany
    {
        return $this->hasMany(Licencia::class);
    }

    public function citaciones(): HasMany
    {
        return $this->hasMany(Citacion::class);
    }
}
