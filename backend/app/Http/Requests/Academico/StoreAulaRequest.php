<?php

namespace App\Http\Requests\Academico;

use App\Enums\EstadoGenerico;
use App\Enums\Turno;
use App\Models\Aula;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreAulaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Aula::class);
    }

    public function rules(): array
    {
        return [
            'gestion_id' => [
                'required', 'integer',
                Rule::exists('gestiones', 'id')->where('unidad_educativa_id', $this->user()->unidad_educativa_id),
            ],
            'grado' => ['required', 'string', 'max:30'],
            'paralelo' => ['required', 'string', 'max:5'],
            'turno' => ['required', new Enum(Turno::class)],
            'estado' => ['nullable', new Enum(EstadoGenerico::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'gestion_id.exists' => 'La gestión no pertenece a su unidad educativa.',
            'grado.required' => 'El grado es obligatorio.',
            'paralelo.required' => 'El paralelo es obligatorio.',
            'turno.required' => 'El turno es obligatorio.',
        ];
    }
}
