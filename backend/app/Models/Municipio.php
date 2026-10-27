<?php

namespace App\Models;

use Database\Factories\MunicipioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Municipio extends Model
{
    /** @use HasFactory<MunicipioFactory> */
    use HasFactory;

    protected $fillable = [
        'nombre',
    ];

    public function macrodistritos(): HasMany
    {
        return $this->hasMany(Macrodistrito::class);
    }
}
