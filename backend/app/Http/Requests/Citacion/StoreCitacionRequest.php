<?php

namespace App\Http\Requests\Citacion;

use App\Enums\TipoCitacion;
use App\Models\Citacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCitacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Citacion::class);
    }

    public function rules(): array
    {
        return [
            'destinatario_id' => ['required', 'integer', 'exists:users,id'],
            'estudiante_id' => ['nullable', 'integer', 'exists:estudiantes,id'],
            'tipo' => ['required', Rule::enum(TipoCitacion::class)],
            'asunto' => ['required', 'string', 'max:255'],
            'mensaje' => ['required', 'string', 'max:5000'],
            'fecha_hora' => ['required', 'date'],
            'lugar' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'destinatario_id.required' => 'Debe seleccionar un destinatario.',
            'destinatario_id.exists' => 'El destinatario seleccionado no existe.',
            'tipo.required' => 'El tipo de citación es obligatorio.',
            'asunto.required' => 'El asunto es obligatorio.',
            'mensaje.required' => 'El mensaje es obligatorio.',
            'fecha_hora.required' => 'La fecha y hora de la citación son obligatorias.',
        ];
    }
}
