<?php

namespace App\Http\Requests\Actividad;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActividadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('actividad'));
    }

    public function rules(): array
    {
        return [
            'titulo' => ['sometimes', 'required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'fecha_cumplimiento' => ['sometimes', 'required', 'date'],
            'fecha_limite_notas' => ['nullable', 'date'],
            'puntaje_maximo' => ['sometimes', 'required', 'numeric', 'min:0.01', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'puntaje_maximo.min' => 'El puntaje máximo debe ser mayor a cero.',
        ];
    }
}
