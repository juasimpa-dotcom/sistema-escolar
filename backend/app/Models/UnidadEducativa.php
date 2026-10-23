<?php

namespace App\Models;

use App\Enums\EstadoUnidad;
use App\Enums\Rol;
use Database\Factories\UnidadEducativaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        'telefono',
        'estado',
        'fecha_baja',
        'motivo_baja',
    ];

    protected function casts(): array
    {
        return [
            'estado' => EstadoUnidad::class,
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
}
