<?php

namespace App\Http\Requests\Academico;

use Illuminate\Foundation\Http\FormRequest;

class RetirarInscripcionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('delete', $this->route('inscripcion'));
    }

    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'max:500'],
            'fecha' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.required' => 'El motivo del retiro es obligatorio.',
            'fecha.required' => 'La fecha de retiro es obligatoria.',
            'fecha.date' => 'La fecha de retiro no es válida.',
        ];
    }
}
