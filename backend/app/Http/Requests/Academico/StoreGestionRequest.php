<?php

namespace App\Http\Requests\Academico;

use App\Enums\EstadoGestion;
use App\Models\Gestion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreGestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Gestion::class);
    }

    public function rules(): array
    {
        return [
            'anio' => [
                'required', 'integer', 'digits:4', 'min:2000', 'max:2100',
                Rule::unique('gestiones', 'anio')->where('unidad_educativa_id', $this->user()->unidad_educativa_id),
            ],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'es_actual' => ['boolean'],
            'estado' => ['nullable', new Enum(EstadoGestion::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'anio.required' => 'El año de la gestión es obligatorio.',
            'anio.unique' => 'Ya existe una gestión para ese año.',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser posterior a la de inicio.',
        ];
    }
}
