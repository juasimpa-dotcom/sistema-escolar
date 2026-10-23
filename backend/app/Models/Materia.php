<?php

namespace App\Models;

use App\Enums\EstadoGenerico;
use App\Models\Concerns\BelongsToUnidadEducativa;
use Database\Factories\MateriaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Materia extends Model
{
    /** @use HasFactory<MateriaFactory> */
    use BelongsToUnidadEducativa, HasFactory, SoftDeletes;

    protected $fillable = [
        'unidad_educativa_id',
        'nombre',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'estado' => EstadoGenerico::class,
        ];
    }

    public function unidadEducativa(): BelongsTo
    {
        return $this->belongsTo(UnidadEducativa::class);
    }

    public function aulaMaterias(): HasMany
    {
        return $this->hasMany(AulaMateria::class);
    }
}
