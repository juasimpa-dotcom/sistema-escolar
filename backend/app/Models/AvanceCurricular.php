<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUnidadEducativa;
use Database\Factories\AvanceCurricularFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvanceCurricular extends Model
{
    /** @use HasFactory<AvanceCurricularFactory> */
    use BelongsToUnidadEducativa, HasFactory;

    protected $table = 'avance_curricular';

    protected $fillable = [
        'unidad_educativa_id',
        'aula_materia_id',
        'periodo_id',
        'planificacion_entregada',
        'fecha_entrega',
        'clases_programadas',
        'clases_dictadas',
    ];

    protected function casts(): array
    {
        return [
            'planificacion_entregada' => 'boolean',
            'fecha_entrega' => 'date',
            'clases_programadas' => 'integer',
            'clases_dictadas' => 'integer',
        ];
    }

    public function unidadEducativa(): BelongsTo
    {
        return $this->belongsTo(UnidadEducativa::class);
    }

    public function aulaMateria(): BelongsTo
    {
        return $this->belongsTo(AulaMateria::class);
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class);
    }
}
