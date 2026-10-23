<?php

namespace App\Http\Requests\Academico;

use App\Enums\DiaSemana;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreHorarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        $aula = $this->route('aula');

        return $aula
            ? $this->user()->can('gestionarHorario', $aula)
            : false;
    }

    public function rules(): array
    {
        return [
            'aula_materia_id' => ['required', 'integer', 'exists:aula_materia,id'],
            'dia' => ['required', new Enum(DiaSemana::class)],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
        ];
    }

    public function messages(): array
    {
        return [
            'aula_materia_id.required' => 'Debe seleccionar la materia del aula.',
            'dia.required' => 'El día es obligatorio.',
            'hora_inicio.required' => 'La hora de inicio es obligatoria.',
            'hora_fin.required' => 'La hora de fin es obligatoria.',
            'hora_fin.after' => 'La hora de fin debe ser mayor a la hora de inicio.',
        ];
    }
}
