<?php

namespace App\Http\Requests\UnidadEducativa;

use App\Models\UnidadEducativa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnidadEducativaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', UnidadEducativa::class);
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'codigo' => ['required', 'string', 'max:50', 'unique:unidades_educativas,codigo'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'director' => ['required', 'array'],
            'director.ci' => ['required', 'string', 'max:30', 'unique:users,ci'],
            'director.nombres' => ['required', 'string', 'max:255'],
            'director.apellidos' => ['required', 'string', 'max:255'],
            'director.email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'director.telefono' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la unidad es obligatorio.',
            'codigo.required' => 'El código es obligatorio.',
            'codigo.unique' => 'El código ya está en uso.',
            'director.required' => 'Debe registrar un director.',
            'director.ci.required' => 'El CI del director es obligatorio.',
            'director.ci.unique' => 'El CI del director ya está registrado.',
            'director.nombres.required' => 'Los nombres del director son obligatorios.',
            'director.apellidos.required' => 'Los apellidos del director son obligatorios.',
        ];
    }
}
