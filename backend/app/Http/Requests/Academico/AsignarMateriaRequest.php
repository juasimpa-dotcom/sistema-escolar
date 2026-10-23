<?php

namespace App\Http\Requests\Academico;

use App\Enums\Rol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AsignarMateriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('gestionarMaterias', $this->route('aula'));
    }

    public function rules(): array
    {
        $aula = $this->route('aula');

        return [
            'materia_id' => [
                'required', 'integer',
                Rule::exists('materias', 'id')->where('unidad_educativa_id', $aula->unidad_educativa_id),
            ],
            'profesor_id' => [
                'required', 'integer',
                Rule::exists('users', 'id')
                    ->where('rol', Rol::PROFESOR->value)
                    ->where('unidad_educativa_id', $aula->unidad_educativa_id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'materia_id.exists' => 'La materia no pertenece a la unidad educativa.',
            'profesor_id.required' => 'Debe asignar un profesor.',
            'profesor_id.exists' => 'El profesor no es válido para esta unidad educativa.',
        ];
    }
}
