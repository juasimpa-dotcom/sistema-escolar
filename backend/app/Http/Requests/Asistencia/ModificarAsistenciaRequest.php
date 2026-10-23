<?php

namespace App\Http\Requests\Asistencia;

use Illuminate\Foundation\Http\FormRequest;

class ModificarAsistenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('asistencia'));
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', 'in:presente,falta,atraso,licencia'],
            'observacion' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado debe ser presente, falta, atraso o licencia.',
        ];
    }
}
