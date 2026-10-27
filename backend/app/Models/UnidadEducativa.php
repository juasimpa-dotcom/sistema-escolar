<?php

namespace App\Models;

use App\Enums\EstadoUnidad;
use App\Enums\Rol;
use App\Enums\TipoUnidad;
use Database\Factories\UnidadEducativaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnidadEducativa extends Model
{
    /** @use HasFactory<UnidadEducativaFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'unidades_educativas';

    protected $fillable = [
        'nombre',
        'codigo',
        'direccion',
        'zona',
        'macrodistrito_id',
        'distrito_municipal_id',
        'telefono',
        'tipo',
        'estado',
        'fecha_baja',
        'motivo_baja',
    ];

    protected function casts(): array
    {
        return [
            'estado' => EstadoUnidad::class,
            'tipo' => TipoUnidad::class,
            'fecha_baja' => 'date',
        ];
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Director de la unidad (relación dedicada para evitar cargar todos
     * los usuarios solo para encontrar al director).
     */
    public function director(): HasOne
    {
        return $this->hasOne(User::class)->where('rol', Rol::DIRECTOR->value);
    }

    public function estudiantes(): HasMany
    {
        return $this->hasMany(Estudiante::class);
    }

    public function gestiones(): HasMany
    {
        return $this->hasMany(Gestion::class);
    }

    public function materias(): HasMany
    {
        return $this->hasMany(Materia::class);
    }

    public function citaciones(): HasMany
    {
        return $this->hasMany(Citacion::class);
    }

    public function macrodistrito(): BelongsTo
    {
        return $this->belongsTo(Macrodistrito::class);
    }

    public function distrito(): BelongsTo
    {
        return $this->belongsTo(DistritoMunicipal::class, 'distrito_municipal_id');
    }

    public function periodos(): HasMany
    {
        return $this->hasMany(Periodo::class);
    }

    public function plazasDocentes(): HasMany
    {
        return $this->hasMany(PlazaDocente::class);
    }

    public function asistenciaDocente(): HasMany
    {
        return $this->hasMany(AsistenciaDocente::class);
    }
}
