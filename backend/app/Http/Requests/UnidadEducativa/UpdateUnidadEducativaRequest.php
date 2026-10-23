<?php

namespace App\Http\Requests\UnidadEducativa;

use App\Enums\EstadoUnidad;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateUnidadEducativaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('unidad'));
    }

    public function rules(): array
    {
        $unidad = $this->route('unidad');

        return [
            'nombre' => ['required', 'string', 'max:255'],
            'codigo' => ['required', 'string', 'max:50', Rule::unique('unidades_educativas', 'codigo')->ignore($unidad->id)],
            'direccion' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'estado' => ['nullable', new Enum(EstadoUnidad::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la unidad es obligatorio.',
            'codigo.unique' => 'El código ya está en uso.',
        ];
    }
}
