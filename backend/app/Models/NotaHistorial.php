<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotaHistorial extends Model
{
    use HasFactory;

    protected $table = 'nota_historial';

    protected $fillable = [
        'nota_id',
        'nota_anterior',
        'nota_nueva',
        'observacion',
        'usuario_id',
    ];

    protected function casts(): array
    {
        return [
            'nota_anterior' => 'decimal:2',
            'nota_nueva' => 'decimal:2',
        ];
    }

    public function nota(): BelongsTo
    {
        return $this->belongsTo(Nota::class, 'nota_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
