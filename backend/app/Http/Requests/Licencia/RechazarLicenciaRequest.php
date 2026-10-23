<?php

namespace App\Http\Requests\Licencia;

use Illuminate\Foundation\Http\FormRequest;

class RechazarLicenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('resolver', $this->route('licencia'));
    }

    public function rules(): array
    {
        return [
            'motivo_rechazo' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo_rechazo.required' => 'Debe indicar el motivo del rechazo.',
        ];
    }
}
