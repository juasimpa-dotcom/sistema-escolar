<?php

namespace App\Models;

use App\Enums\EstadoUsuario;
use App\Enums\Rol;
use App\Models\Concerns\BelongsToUnidadEducativa;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use BelongsToUnidadEducativa, HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'unidad_educativa_id',
        'rol',
        'ci',
        'usuario',
        'nombres',
        'apellidos',
        'email',
        'telefono',
        'password',
        'debe_cambiar_password',
        'estado',
        'fecha_baja',
        'motivo_baja',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'rol' => Rol::class,
            'estado' => EstadoUsuario::class,
            'debe_cambiar_password' => 'boolean',
            'password' => 'hashed',
            'fecha_baja' => 'date',
        ];
    }

    public function unidadEducativa(): BelongsTo
    {
        return $this->belongsTo(UnidadEducativa::class);
    }

    public function estudiante(): HasOne
    {
        return $this->hasOne(Estudiante::class);
    }

    public function hijos(): BelongsToMany
    {
        return $this->belongsToMany(Estudiante::class, 'padre_estudiante', 'padre_id', 'estudiante_id')
            ->withPivot('parentesco')
            ->withTimestamps();
    }

    public function materiasAsignadas(): HasMany
    {
        return $this->hasMany(AulaMateria::class, 'profesor_id');
    }

    public function citacionesEnviadas(): HasMany
    {
        return $this->hasMany(Citacion::class, 'emisor_id');
    }

    public function citacionesRecibidas(): HasMany
    {
        return $this->hasMany(Citacion::class, 'destinatario_id');
    }

    public function licenciasSolicitadas(): HasMany
    {
        return $this->hasMany(Licencia::class, 'padre_id');
    }

    public function nombreCompleto(): string
    {
        return trim("{$this->nombres} {$this->apellidos}");
    }

    public function esSuperadmin(): bool
    {
        return $this->rol === Rol::SUPERADMIN;
    }
}
