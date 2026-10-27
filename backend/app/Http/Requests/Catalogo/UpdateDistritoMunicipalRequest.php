<?php

namespace App\Http\Requests\Catalogo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDistritoMunicipalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('distrito'));
    }

    public function rules(): array
    {
        $distrito = $this->route('distrito');

        return [
            'macrodistrito_id' => ['nullable', 'integer', Rule::exists('macrodistritos', 'id')],
            'numero' => ['required', 'integer', 'min:1', 'max:99', Rule::unique('distritos_municipales', 'numero')->ignore($distrito->id)],
            'nombre' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'numero.required' => 'El número del distrito es obligatorio.',
            'numero.unique' => 'Ese número de distrito municipal ya existe.',
            'macrodistrito_id.exists' => 'El macrodistrito seleccionado no existe.',
        ];
    }
}
