<?php

namespace App\Models;

use App\Enums\TipoMacrodistrito;
use Database\Factories\MacrodistritoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Macrodistrito extends Model
{
    /** @use HasFactory<MacrodistritoFactory> */
    use HasFactory;

    protected $fillable = [
        'municipio_id',
        'codigo',
        'nombre',
        'tipo',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoMacrodistrito::class,
        ];
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class);
    }

    public function distritosMunicipales(): HasMany
    {
        return $this->hasMany(DistritoMunicipal::class);
    }

    public function unidadesEducativas(): HasMany
    {
        return $this->hasMany(UnidadEducativa::class);
    }
}
