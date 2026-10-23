<?php

namespace App\Models;

use App\Enums\EstadoAsistencia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsistenciaHistorial extends Model
{
    use HasFactory;

    protected $table = 'asistencia_historial';

    protected $fillable = [
        'asistencia_id',
        'estado_anterior',
        'estado_nuevo',
        'observacion',
        'usuario_id',
    ];

    protected function casts(): array
    {
        return [
            'estado_anterior' => EstadoAsistencia::class,
            'estado_nuevo' => EstadoAsistencia::class,
        ];
    }

    public function asistencia(): BelongsTo
    {
        return $this->belongsTo(Asistencia::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
