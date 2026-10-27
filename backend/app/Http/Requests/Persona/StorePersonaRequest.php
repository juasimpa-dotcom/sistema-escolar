<?php

namespace App\Http\Requests\Persona;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePersonaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    public function rules(): array
    {
        $rolesPermitidos = $this->rolesPermitidos();

        return [
            'rol' => ['required', Rule::in($rolesPermitidos)],
            'ci' => ['required', 'string', 'regex:/^[0-9]+$/', 'max:30', 'unique:users,ci'],
            'nombres' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'telefono' => ['nullable', 'string', 'max:30'],
        ];
    }

    /**
     * Roles que el usuario autenticado puede crear.
     * Superadmin -> Director. Director/Administrativo -> resto de personas.
     */
    public function rolesPermitidos(): array
    {
        $user = $this->user();

        if ($user->rol === Rol::SUPERADMIN) {
            return [Rol::DIRECTOR->value];
        }

        return [
            Rol::PROFESOR->value,
            Rol::ADMINISTRATIVO->value,
            Rol::PADRE->value,
        ];
    }

    public function messages(): array
    {
        return [
            'rol.required' => 'Debe indicar el rol de la persona.',
            'rol.in' => 'No tiene permiso para crear ese rol.',
            'ci.required' => 'El CI es obligatorio.',
            'ci.regex' => 'El CI solo debe contener números.',
            'ci.unique' => 'El CI ya está registrado.',
            'nombres.required' => 'Los nombres son obligatorios.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
        ];
    }
}
