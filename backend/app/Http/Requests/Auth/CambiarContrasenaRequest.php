<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class CambiarContrasenaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password_actual' => ['required', 'string', 'regex:/^[0-9]+$/'],
            'password' => ['required', 'string', 'regex:/^[0-9]+$/', 'min:6', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'password_actual.required' => 'La contraseña actual es obligatoria.',
            'password_actual.regex' => 'La contraseña actual solo debe contener números.',
            'password.required' => 'La nueva contraseña es obligatoria.',
            'password.regex' => 'La nueva contraseña solo debe contener números.',
            'password.min' => 'La nueva contraseña debe tener al menos 6 caracteres.',
            'password.confirmed' => 'La confirmación de la nueva contraseña no coincide.',
        ];
    }
}
