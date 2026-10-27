<?php

namespace App\Models;

use App\Enums\EstadoAsistencia;
use App\Models\Concerns\BelongsToUnidadEducativa;
use Database\Factories\AsistenciaDocenteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsistenciaDocente extends Model
{
    /** @use HasFactory<AsistenciaDocenteFactory> */
    use BelongsToUnidadEducativa, HasFactory;

    protected $table = 'asistencia_docente';

    protected $fillable = [
        'unidad_educativa_id',
        'profesor_id',
        'fecha',
        'estado',
        'hora_ingreso',
        'observacion',
        'registrado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'estado' => EstadoAsistencia::class,
            'hora_ingreso' => 'datetime:H:i',
        ];
    }

    public function unidadEducativa(): BelongsTo
    {
        return $this->belongsTo(UnidadEducativa::class);
    }

    public function profesor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'profesor_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
