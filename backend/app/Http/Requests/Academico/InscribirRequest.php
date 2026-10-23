<?php

namespace App\Http\Requests\Academico;

use App\Models\Inscripcion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InscribirRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Inscripcion::class);
    }

    public function rules(): array
    {
        return [
            'estudiante_id' => [
                'required', 'integer',
                Rule::exists('estudiantes', 'id')->where('unidad_educativa_id', $this->user()->unidad_educativa_id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'estudiante_id.required' => 'Debe seleccionar un estudiante.',
            'estudiante_id.exists' => 'El estudiante no pertenece a su unidad educativa.',
        ];
    }
}
