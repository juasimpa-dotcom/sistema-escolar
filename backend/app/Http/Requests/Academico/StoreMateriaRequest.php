<?php

namespace App\Http\Requests\Academico;

use App\Enums\EstadoGenerico;
use App\Models\Materia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreMateriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Materia::class);
    }

    public function rules(): array
    {
        return [
            'nombre' => [
                'required', 'string', 'max:255',
                Rule::unique('materias', 'nombre')->where('unidad_educativa_id', $this->user()->unidad_educativa_id),
            ],
            'estado' => ['nullable', new Enum(EstadoGenerico::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la materia es obligatorio.',
            'nombre.unique' => 'Ya existe una materia con ese nombre.',
        ];
    }
}
