<?php

namespace App\Http\Requests\Academico;

use App\Enums\EstadoGenerico;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateMateriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('materia'));
    }

    public function rules(): array
    {
        $materia = $this->route('materia');

        return [
            'nombre' => [
                'required', 'string', 'max:255',
                Rule::unique('materias', 'nombre')
                    ->where('unidad_educativa_id', $materia->unidad_educativa_id)
                    ->ignore($materia->id),
            ],
            'estado' => ['nullable', new Enum(EstadoGenerico::class)],
        ];
    }
}
