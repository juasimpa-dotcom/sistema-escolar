<?php

namespace App\Models;

use App\Enums\DiaSemana;
use Database\Factories\HorarioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Horario extends Model
{
    /** @use HasFactory<HorarioFactory> */
    use HasFactory;

    protected $fillable = [
        'aula_materia_id',
        'dia',
        'hora_inicio',
        'hora_fin',
    ];

    protected function casts(): array
    {
        return [
            'dia' => DiaSemana::class,
        ];
    }

    public function aulaMateria(): BelongsTo
    {
        return $this->belongsTo(AulaMateria::class);
    }
}
