<?php

namespace App\Http\Requests\Persona;

use App\Enums\Parentesco;
use App\Enums\Rol;
use App\Models\Estudiante;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreEstudianteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Estudiante::class);
    }

    public function rules(): array
    {
        return [
            'estudiante' => ['required', 'array'],
            'estudiante.ci' => ['required', 'string', 'max:30', 'unique:users,ci'],
            'estudiante.nombres' => ['required', 'string', 'max:255'],
            'estudiante.apellidos' => ['required', 'string', 'max:255'],
            'estudiante.fecha_nacimiento' => ['nullable', 'date'],
            'estudiante.email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'estudiante.telefono' => ['nullable', 'string', 'max:30'],

            'padre_existente_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('rol', Rol::PADRE->value),
            ],
            'padre_nuevo' => ['nullable', 'array', 'required_without:padre_existente_id'],
            'padre_nuevo.ci' => ['required_with:padre_nuevo', 'string', 'max:30', 'unique:users,ci'],
            'padre_nuevo.nombres' => ['required_with:padre_nuevo', 'string', 'max:255'],
            'padre_nuevo.apellidos' => ['required_with:padre_nuevo', 'string', 'max:255'],
            'padre_nuevo.email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'padre_nuevo.telefono' => ['nullable', 'string', 'max:30'],

            'parentesco' => ['nullable', new Enum(Parentesco::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'estudiante.ci.required' => 'El CI del estudiante es obligatorio.',
            'estudiante.ci.unique' => 'El CI del estudiante ya está registrado.',
            'estudiante.nombres.required' => 'Los nombres del estudiante son obligatorios.',
            'estudiante.apellidos.required' => 'Los apellidos del estudiante son obligatorios.',
            'padre_existente_id.exists' => 'El padre seleccionado no existe o no tiene rol de padre.',
            'padre_nuevo.required_without' => 'Debe asociar un padre existente o registrar uno nuevo.',
            'padre_nuevo.ci.required_with' => 'El CI del padre es obligatorio.',
            'padre_nuevo.ci.unique' => 'El CI del padre ya está registrado.',
            'padre_nuevo.nombres.required_with' => 'Los nombres del padre son obligatorios.',
            'padre_nuevo.apellidos.required_with' => 'Los apellidos del padre son obligatorios.',
        ];
    }
}
