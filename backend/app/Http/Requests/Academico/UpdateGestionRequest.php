<?php

namespace App\Http\Requests\Academico;

use App\Enums\EstadoGestion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateGestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('gestion'));
    }

    public function rules(): array
    {
        $gestion = $this->route('gestion');

        return [
            'anio' => [
                'required', 'integer', 'digits:4', 'min:2000', 'max:2100',
                Rule::unique('gestiones', 'anio')
                    ->where('unidad_educativa_id', $gestion->unidad_educativa_id)
                    ->ignore($gestion->id),
            ],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'es_actual' => ['boolean'],
            'estado' => ['nullable', new Enum(EstadoGestion::class)],
        ];
    }
}
