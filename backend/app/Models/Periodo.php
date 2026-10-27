<?php

namespace App\Models;

use App\Enums\TipoPeriodo;
use App\Models\Concerns\BelongsToUnidadEducativa;
use Database\Factories\PeriodoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Periodo extends Model
{
    /** @use HasFactory<PeriodoFactory> */
    use BelongsToUnidadEducativa, HasFactory, SoftDeletes;

    protected $fillable = [
        'unidad_educativa_id',
        'gestion_id',
        'nombre',
        'tipo',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoPeriodo::class,
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    public function unidadEducativa(): BelongsTo
    {
        return $this->belongsTo(UnidadEducativa::class);
    }

    public function gestion(): BelongsTo
    {
        return $this->belongsTo(Gestion::class);
    }

    public function actividades(): HasMany
    {
        return $this->hasMany(Actividad::class);
    }

    public function avanceCurricular(): HasMany
    {
        return $this->hasMany(AvanceCurricular::class);
    }
}
