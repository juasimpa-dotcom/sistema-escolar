<?php

namespace App\Http\Requests\Licencia;

use App\Models\Estudiante;
use App\Models\Licencia;
use Illuminate\Foundation\Http\FormRequest;

class StoreLicenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $estudiante = Estudiante::find($this->integer('estudiante_id'));

        return $estudiante !== null
            && $this->user()->can('create', [Licencia::class, $estudiante]);
    }

    public function rules(): array
    {
        return [
            'estudiante_id' => ['required', 'integer', 'exists:estudiantes,id'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'motivo' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'estudiante_id.required' => 'Debe seleccionar un hijo.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_fin.required' => 'La fecha de fin es obligatoria.',
            'fecha_fin.after_or_equal' => 'La fecha fin no puede ser anterior a la fecha inicio.',
            'motivo.required' => 'El motivo de la licencia es obligatorio.',
        ];
    }
}
