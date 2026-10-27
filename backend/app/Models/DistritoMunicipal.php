<?php

namespace App\Models;

use Database\Factories\DistritoMunicipalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DistritoMunicipal extends Model
{
    /** @use HasFactory<DistritoMunicipalFactory> */
    use HasFactory;

    protected $table = 'distritos_municipales';

    protected $fillable = [
        'macrodistrito_id',
        'numero',
        'nombre',
    ];

    protected function casts(): array
    {
        return [
            'numero' => 'integer',
        ];
    }

    public function macrodistrito(): BelongsTo
    {
        return $this->belongsTo(Macrodistrito::class);
    }

    public function unidadesEducativas(): HasMany
    {
        return $this->hasMany(UnidadEducativa::class);
    }
}
