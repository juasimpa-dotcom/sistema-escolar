<?php

namespace App\Http\Requests\Licencia;

use Illuminate\Foundation\Http\FormRequest;

class AprobarLicenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('resolver', $this->route('licencia'));
    }

    public function rules(): array
    {
        return [];
    }
}
