<?php

namespace App\Services;

use App\Enums\EstadoCitacion;
use App\Enums\Rol;
use App\Models\Citacion;
use App\Models\Estudiante;
use App\Models\User;
use App\Notifications\CitacionRecibida;
use Illuminate\Validation\ValidationException;

class CitacionService
{
    /**
     * Registra una citación o comunicado.
     *
     * Reglas de destinatario:
     * - tipo "padre": el destinatario debe ser un padre/tutor del estudiante indicado.
     * - tipo "profesor_informativa" / "profesor_extraordinaria": el destinatario
     *   debe ser un profesor de la misma unidad (el estudiante no aplica).
     */
    public function crear(User $emisor, array $datos): Citacion
    {
        $destinatario = User::findOrFail($datos['destinatario_id']);

        $this->assertDestinatarioValido($emisor, $datos['tipo'], $destinatario, $datos['estudiante_id'] ?? null);

        $citacion = Citacion::create([
            'unidad_educativa_id' => $destinatario->unidad_educativa_id ?? $emisor->unidad_educativa_id,
            'emisor_id' => $emisor->id,
            'destinatario_id' => $destinatario->id,
            'estudiante_id' => $datos['estudiante_id'] ?? null,
            'tipo' => $datos['tipo'],
            'asunto' => $datos['asunto'],
            'mensaje' => $datos['mensaje'],
            'fecha_hora' => $datos['fecha_hora'],
            'lugar' => $datos['lugar'] ?? null,
            'estado' => EstadoCitacion::ENVIADA,
        ]);

        $destinatario->notify(new CitacionRecibida($citacion));

        return $citacion;
    }

    public function marcarVista(Citacion $citacion): Citacion
    {
        if ($citacion->estado === EstadoCitacion::VISTA) {
            return $citacion;
        }

        $citacion->update([
            'estado' => EstadoCitacion::VISTA,
            'leida_en' => now(),
        ]);

        return $citacion->refresh();
    }

    private function assertDestinatarioValido(User $emisor, string $tipo, User $destinatario, ?int $estudianteId): void
    {
        if ($destinatario->rol === Rol::PROFESOR) {
            if ($tipo === 'padre') {
                throw ValidationException::withMessages([
                    'destinatario_id' => ['Una citación a padre de familia debe dirigirse a un padre/tutor.'],
                ]);
            }

            $this->assertMismaUnidad($emisor, $destinatario);

            return;
        }

        if ($destinatario->rol !== Rol::PADRE) {
            throw ValidationException::withMessages([
                'destinatario_id' => ['El destinatario debe ser un padre de familia o un profesor.'],
            ]);
        }

        if ($tipo !== 'padre') {
            throw ValidationException::withMessages([
                'tipo' => ['Los comunicados y citaciones extraordinarias se dirigen a profesores.'],
            ]);
        }

        $this->assertMismaUnidad($emisor, $destinatario);

        if ($estudianteId === null) {
            throw ValidationException::withMessages([
                'estudiante_id' => ['Debe indicar el estudiante al que se refiere la citación.'],
            ]);
        }

        $estudiante = Estudiante::findOrFail($estudianteId);

        $esTutor = $destinatario->hijos()->whereKey($estudiante->id)->exists();

        if (! $esTutor) {
            throw ValidationException::withMessages([
                'destinatario_id' => ['El destinatario no es tutor del estudiante indicado.'],
            ]);
        }

        if ($emisor->unidad_educativa_id !== null && $estudiante->unidad_educativa_id !== $emisor->unidad_educativa_id) {
            throw ValidationException::withMessages([
                'estudiante_id' => ['El estudiante no pertenece a su unidad educativa.'],
            ]);
        }
    }

    private function assertMismaUnidad(User $emisor, User $destinatario): void
    {
        if ($emisor->unidad_educativa_id !== null && $destinatario->unidad_educativa_id !== $emisor->unidad_educativa_id) {
            throw ValidationException::withMessages([
                'destinatario_id' => ['El destinatario no pertenece a su unidad educativa.'],
            ]);
        }
    }
}
