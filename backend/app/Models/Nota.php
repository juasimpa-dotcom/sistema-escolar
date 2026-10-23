<?php

namespace App\Models;

use Database\Factories\NotaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Nota extends Model
{
    /** @use HasFactory<NotaFactory> */
    use HasFactory;

    protected $fillable = [
        'actividad_id',
        'estudiante_id',
        'nota',
        'observacion',
        'fue_modificada',
        'registrado_por',
        'modificado_por',
    ];

    protected function casts(): array
    {
        return [
            'nota' => 'decimal:2',
            'fue_modificada' => 'boolean',
        ];
    }

    public function actividad(): BelongsTo
    {
        return $this->belongsTo(Actividad::class);
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function modificadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'modificado_por');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(NotaHistorial::class, 'nota_id');
    }
}
