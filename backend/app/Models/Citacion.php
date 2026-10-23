<?php

namespace App\Models;

use App\Enums\EstadoCitacion;
use App\Enums\TipoCitacion;
use App\Models\Concerns\BelongsToUnidadEducativa;
use Database\Factories\CitacionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Citacion extends Model
{
    /** @use HasFactory<CitacionFactory> */
    use BelongsToUnidadEducativa, HasFactory;

    protected $table = 'citaciones';

    protected $fillable = [
        'unidad_educativa_id',
        'emisor_id',
        'destinatario_id',
        'estudiante_id',
        'tipo',
        'asunto',
        'mensaje',
        'fecha_hora',
        'lugar',
        'estado',
        'leida_en',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoCitacion::class,
            'estado' => EstadoCitacion::class,
            'fecha_hora' => 'datetime',
            'leida_en' => 'datetime',
        ];
    }

    public function unidadEducativa(): BelongsTo
    {
        return $this->belongsTo(UnidadEducativa::class);
    }

    public function emisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'emisor_id');
    }

    public function destinatario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'destinatario_id');
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }
}
