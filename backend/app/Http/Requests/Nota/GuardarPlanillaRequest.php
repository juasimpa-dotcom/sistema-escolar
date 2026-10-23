<?php

namespace App\Http\Requests\Nota;

use App\Models\Nota;
use Illuminate\Foundation\Http\FormRequest;

class GuardarPlanillaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [Nota::class, $this->route('actividad')]);
    }

    public function rules(): array
    {
        return [
            'notas' => ['required', 'array', 'min:1'],
            'notas.*.estudiante_id' => ['required', 'integer', 'exists:estudiantes,id'],
            'notas.*.nota' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'notas.required' => 'Debe enviar al menos una nota.',
            'notas.*.nota.required' => 'Todas las filas deben tener una nota.',
            'notas.*.nota.min' => 'La nota no puede ser negativa.',
        ];
    }
}
