<?php

namespace App\Http\Requests\Asistencia;

use App\Models\Asistencia;
use Illuminate\Foundation\Http\FormRequest;

class RegistrarTomaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $aulaMateria = $this->route('aulaMateria');

        return $aulaMateria
            ? $this->user()->can('registrar', [Asistencia::class, $aulaMateria])
            : false;
    }

    public function rules(): array
    {
        return [
            'fecha' => ['required', 'date'],
            'registros' => ['required', 'array', 'min:1'],
            'registros.*.estudiante_id' => ['required', 'integer', 'exists:estudiantes,id'],
            'registros.*.estado' => ['required', 'in:presente,falta,atraso,licencia'],
            'registros.*.observacion' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha.required' => 'La fecha es obligatoria.',
            'registros.required' => 'Debe enviar la lista de estudiantes.',
            'registros.*.estado.in' => 'El estado debe ser presente, falta, atraso o licencia.',
        ];
    }
}
