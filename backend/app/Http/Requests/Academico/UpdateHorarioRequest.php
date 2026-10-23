<?php

namespace App\Http\Requests\Academico;

use App\Enums\DiaSemana;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateHorarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        $horario = $this->route('horario');

        return $horario && $horario->aulaMateria
            ? $this->user()->can('gestionarHorario', $horario->aulaMateria->aula)
            : false;
    }

    public function rules(): array
    {
        return [
            'dia' => ['required', new Enum(DiaSemana::class)],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
        ];
    }

    public function messages(): array
    {
        return [
            'hora_fin.after' => 'La hora de fin debe ser mayor a la hora de inicio.',
        ];
    }
}
