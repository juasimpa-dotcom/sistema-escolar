<?php

namespace App\Http\Requests\Actividad;

use App\Models\Actividad;
use Illuminate\Foundation\Http\FormRequest;

class StoreActividadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $aulaMateria = $this->route('aulaMateria');

        return $aulaMateria
            ? $this->user()->can('create', [$this->actividadClass(), $aulaMateria])
            : false;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'fecha_cumplimiento' => ['required', 'date'],
            'fecha_limite_notas' => ['nullable', 'date', 'after_or_equal:fecha_cumplimiento'],
            'puntaje_maximo' => ['required', 'numeric', 'min:0.01', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'El título de la actividad es obligatorio.',
            'fecha_cumplimiento.required' => 'La fecha de cumplimiento es obligatoria.',
            'fecha_limite_notas.after_or_equal' => 'La fecha límite de notas no puede ser anterior a la fecha de cumplimiento.',
            'puntaje_maximo.required' => 'El puntaje máximo es obligatorio.',
            'puntaje_maximo.min' => 'El puntaje máximo debe ser mayor a cero.',
        ];
    }

    private function actividadClass(): string
    {
        return Actividad::class;
    }
}
