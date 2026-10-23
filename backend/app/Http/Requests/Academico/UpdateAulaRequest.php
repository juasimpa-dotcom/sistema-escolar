<?php

namespace App\Http\Requests\Academico;

use App\Enums\EstadoGenerico;
use App\Enums\Turno;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateAulaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('aula'));
    }

    public function rules(): array
    {
        return [
            'grado' => ['required', 'string', 'max:30'],
            'paralelo' => ['required', 'string', 'max:5'],
            'turno' => ['required', new Enum(Turno::class)],
            'estado' => ['nullable', new Enum(EstadoGenerico::class)],
        ];
    }
}
