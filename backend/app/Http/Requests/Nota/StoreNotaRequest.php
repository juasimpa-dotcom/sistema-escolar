<?php

namespace App\Http\Requests\Nota;

use App\Models\Nota;
use Illuminate\Foundation\Http\FormRequest;

class StoreNotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [Nota::class, $this->route('actividad')]);
    }

    public function rules(): array
    {
        return [
            'estudiante_id' => ['required', 'integer', 'exists:estudiantes,id'],
            'nota' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'estudiante_id.required' => 'Debe seleccionar un estudiante.',
            'nota.required' => 'La nota es obligatoria.',
            'nota.min' => 'La nota no puede ser negativa.',
        ];
    }
}
