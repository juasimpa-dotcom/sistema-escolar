<?php

namespace App\Http\Requests\Persona;

use Illuminate\Foundation\Http\FormRequest;

class DarDeBajaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $modelo = $this->route('persona') ?? $this->route('estudiante');

        return $modelo
            ? $this->user()->can('darDeBaja', $modelo)
            : false;
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
            'motivo.required' => 'El motivo de la baja es obligatorio.',
            'fecha.required' => 'La fecha de baja es obligatoria.',
            'fecha.date' => 'La fecha de baja no es válida.',
        ];
    }
}
