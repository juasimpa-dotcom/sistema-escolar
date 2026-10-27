<?php

namespace App\Http\Requests\UnidadEducativa;

use App\Enums\EstadoUnidad;
use App\Enums\TipoUnidad;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Exists;

class UpdateUnidadEducativaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('unidad'));
    }

    public function rules(): array
    {
        $unidad = $this->route('unidad');

        return [
            'nombre' => ['required', 'string', 'max:255'],
            'codigo' => ['required', 'string', 'max:50', Rule::unique('unidades_educativas', 'codigo')->ignore($unidad->id)],
            'direccion' => ['nullable', 'string', 'max:255'],
            'zona' => ['nullable', 'string', 'max:255'],
            'macrodistrito_id' => ['nullable', 'integer', Rule::exists('macrodistritos', 'id')],
            'distrito_municipal_id' => ['nullable', 'integer', $this->reglaDistrito()],
            'tipo' => ['nullable', new Enum(TipoUnidad::class)],
            'telefono' => ['nullable', 'string', 'max:30'],
            'estado' => ['nullable', new Enum(EstadoUnidad::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la unidad es obligatorio.',
            'codigo.unique' => 'El código ya está en uso.',
            'distrito_municipal_id.exists' => 'El distrito municipal no pertenece al macrodistrito seleccionado.',
        ];
    }

    private function reglaDistrito(): Exists
    {
        $regla = Rule::exists('distritos_municipales', 'id');

        if ($this->filled('macrodistrito_id')) {
            $regla->where('macrodistrito_id', $this->integer('macrodistrito_id'));
        }

        return $regla;
    }
}
