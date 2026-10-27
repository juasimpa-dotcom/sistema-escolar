<?php

namespace App\Models;

use App\Enums\TipoFichaEvento;
use App\Models\Concerns\BelongsToUnidadEducativa;
use Database\Factories\FichaEstudianteEventoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FichaEstudianteEvento extends Model
{
    /** @use HasFactory<FichaEstudianteEventoFactory> */
    use BelongsToUnidadEducativa, HasFactory;

    protected $fillable = [
        'unidad_educativa_id',
        'estudiante_id',
        'tipo',
        'fecha',
        'descripcion',
        'registrado_por',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoFichaEvento::class,
            'fecha' => 'date',
        ];
    }

    public function unidadEducativa(): BelongsTo
    {
        return $this->belongsTo(UnidadEducativa::class);
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
