<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\Rol;
use Illuminate\Foundation\Http\FormRequest;

class ResumenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()->rol, [Rol::SUPERADMIN, Rol::DIRECTOR, Rol::ADMINISTRATIVO], true);
    }

    public function rules(): array
    {
        return [
            'fecha' => ['nullable', 'date'],
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
            'aula_id' => ['nullable', 'integer', 'exists:aulas,id'],
            'umbral' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_hasta.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
            'umbral.numeric' => 'El umbral debe ser un número.',
        ];
    }
}
