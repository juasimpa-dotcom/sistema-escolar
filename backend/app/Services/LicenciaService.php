<?php

namespace App\Services;

use App\Enums\EstadoLicencia;
use App\Enums\EstadoUsuario;
use App\Enums\Rol;
use App\Models\Estudiante;
use App\Models\Licencia;
use App\Models\User;
use App\Notifications\LicenciaResuelta;
use App\Notifications\LicenciaSolicitada;
use Illuminate\Validation\ValidationException;

class LicenciaService
{
    public function solicitar(Estudiante $estudiante, User $padre, array $datos): Licencia
    {
        if ($datos['fecha_fin'] < $datos['fecha_inicio']) {
            throw ValidationException::withMessages([
                'fecha_fin' => ['La fecha fin no puede ser anterior a la fecha inicio.'],
            ]);
        }

        $solapada = Licencia::query()
            ->where('estudiante_id', $estudiante->id)
            ->where('estado', EstadoLicencia::PENDIENTE->value)
            ->where('fecha_inicio', '<=', $datos['fecha_fin'])
            ->where('fecha_fin', '>=', $datos['fecha_inicio'])
            ->exists();

        if ($solapada) {
            throw ValidationException::withMessages([
                'fecha_inicio' => ['Ya existe una solicitud de licencia pendiente que se solapa con esas fechas.'],
            ]);
        }

        $licencia = Licencia::create([
            'estudiante_id' => $estudiante->id,
            'padre_id' => $padre->id,
            'fecha_inicio' => $datos['fecha_inicio'],
            'fecha_fin' => $datos['fecha_fin'],
            'motivo' => $datos['motivo'],
            'estado' => EstadoLicencia::PENDIENTE,
        ]);

        $this->notificarSolicitud($licencia->load(['estudiante.user', 'padre']));

        return $licencia;
    }

    public function aprobar(Licencia $licencia, User $director): Licencia
    {
        $this->assertPendiente($licencia);

        $licencia->update([
            'estado' => EstadoLicencia::APROBADA,
            'resuelto_por' => $director->id,
            'fecha_resolucion' => now(),
            'motivo_rechazo' => null,
        ]);

        $this->notificarResolucion($licencia->refresh());

        return $licencia;
    }

    public function rechazar(Licencia $licencia, User $director, string $motivo): Licencia
    {
        $this->assertPendiente($licencia);

        if (trim($motivo) === '') {
            throw ValidationException::withMessages([
                'motivo_rechazo' => ['Debe indicar el motivo del rechazo.'],
            ]);
        }

        $licencia->update([
            'estado' => EstadoLicencia::RECHAZADA,
            'resuelto_por' => $director->id,
            'fecha_resolucion' => now(),
            'motivo_rechazo' => $motivo,
        ]);

        $this->notificarResolucion($licencia->refresh());

        return $licencia;
    }

    private function notificarResolucion(Licencia $licencia): void
    {
        $padre = $licencia->padre;

        if ($padre !== null) {
            $padre->notify(new LicenciaResuelta($licencia->load('estudiante.user')));
        }
    }

    private function notificarSolicitud(Licencia $licencia): void
    {
        $unidadId = $licencia->estudiante?->unidad_educativa_id;

        if ($unidadId === null) {
            return;
        }

        User::query()
            ->where('unidad_educativa_id', $unidadId)
            ->where('rol', Rol::DIRECTOR->value)
            ->where('estado', EstadoUsuario::ACTIVO->value)
            ->get()
            ->each(fn (User $director) => $director->notify(new LicenciaSolicitada($licencia)));
    }

    private function assertPendiente(Licencia $licencia): void
    {
        if ($licencia->estado !== EstadoLicencia::PENDIENTE) {
            throw ValidationException::withMessages([
                'estado' => ['La licencia ya fue resuelta (aprobada o rechazada).'],
            ]);
        }
    }
}
