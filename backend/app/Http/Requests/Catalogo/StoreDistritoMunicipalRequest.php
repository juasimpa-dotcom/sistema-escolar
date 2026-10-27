<?php

namespace App\Http\Requests\Catalogo;

use App\Models\DistritoMunicipal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDistritoMunicipalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', DistritoMunicipal::class);
    }

    public function rules(): array
    {
        return [
            'macrodistrito_id' => ['nullable', 'integer', Rule::exists('macrodistritos', 'id')],
            'numero' => ['required', 'integer', 'min:1', 'max:99', 'unique:distritos_municipales,numero'],
            'nombre' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'numero.required' => 'El número del distrito es obligatorio.',
            'numero.unique' => 'Ese número de distrito municipal ya existe.',
            'numero.min' => 'El número de distrito debe ser mayor a 0.',
            'macrodistrito_id.exists' => 'El macrodistrito seleccionado no existe.',
        ];
    }
}
