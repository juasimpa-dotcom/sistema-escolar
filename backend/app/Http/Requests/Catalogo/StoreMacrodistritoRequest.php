<?php

namespace App\Http\Requests\Catalogo;

use App\Enums\TipoMacrodistrito;
use App\Models\Macrodistrito;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreMacrodistritoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Macrodistrito::class);
    }

    public function rules(): array
    {
        return [
            'municipio_id' => ['nullable', 'integer', Rule::exists('municipios', 'id')],
            'codigo' => ['required', 'string', 'max:10', 'unique:macrodistritos,codigo'],
            'nombre' => ['required', 'string', 'max:255'],
            'tipo' => ['required', new Enum(TipoMacrodistrito::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'El código del macrodistrito es obligatorio.',
            'codigo.unique' => 'El código del macrodistrito ya existe.',
            'nombre.required' => 'El nombre del macrodistrito es obligatorio.',
            'tipo.required' => 'El tipo (urbano/rural) es obligatorio.',
        ];
    }
}
