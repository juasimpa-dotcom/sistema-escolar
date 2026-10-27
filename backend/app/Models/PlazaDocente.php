<?php

namespace App\Models;

use App\Enums\EstadoPlaza;
use App\Enums\NivelEducativo;
use App\Models\Concerns\BelongsToUnidadEducativa;
use Database\Factories\PlazaDocenteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlazaDocente extends Model
{
    /** @use HasFactory<PlazaDocenteFactory> */
    use BelongsToUnidadEducativa, HasFactory, SoftDeletes;

    protected $table = 'plazas_docentes';

    protected $fillable = [
        'unidad_educativa_id',
        'materia_id',
        'nivel',
        'estado',
        'profesor_id',
    ];

    protected function casts(): array
    {
        return [
            'nivel' => NivelEducativo::class,
            'estado' => EstadoPlaza::class,
        ];
    }

    public function unidadEducativa(): BelongsTo
    {
        return $this->belongsTo(UnidadEducativa::class);
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class);
    }

    public function profesor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'profesor_id');
    }

    public function scopeVacias($query)
    {
        return $query->where('estado', EstadoPlaza::VACANTE->value);
    }

    public function scopeOcupadas($query)
    {
        return $query->where('estado', EstadoPlaza::OCUPADA->value);
    }
}
