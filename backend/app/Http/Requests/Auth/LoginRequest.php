<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ci' => ['required', 'string', 'regex:/^[0-9]+$/', 'max:30'],
            'password' => ['required', 'string', 'regex:/^[0-9]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'ci.required' => 'El CI es obligatorio.',
            'ci.regex' => 'El CI solo debe contener números.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.regex' => 'La contraseña solo debe contener números.',
        ];
    }
}
