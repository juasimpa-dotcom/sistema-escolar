<?php

namespace App\Http\Requests\Nota;

use Illuminate\Foundation\Http\FormRequest;

class ModificarNotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('nota'));
    }

    public function rules(): array
    {
        return [
            'nota' => ['required', 'numeric', 'min:0'],
            'observacion' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'nota.required' => 'La nueva nota es obligatoria.',
            'nota.min' => 'La nota no puede ser negativa.',
            'observacion.required' => 'La observación es obligatoria al corregir una nota.',
        ];
    }
}
