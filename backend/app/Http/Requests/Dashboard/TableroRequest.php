<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Filtros del tablero por rol (GET /dashboard).
 */
class TableroRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Cualquier usuario autenticado tiene un panel de inicio propio;
        // el alcance de los datos lo determina el rol en DashboardService.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'macrodistrito_id' => ['nullable', 'integer', 'exists:macrodistritos,id'],
            'distrito_municipal_id' => ['nullable', 'integer', 'exists:distritos_municipales,id'],
            'umbral' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'umbral.numeric' => 'El umbral debe ser un número.',
            'macrodistrito_id.exists' => 'El macrodistrito no existe.',
            'distrito_municipal_id.exists' => 'El distrito municipal no existe.',
        ];
    }
}
