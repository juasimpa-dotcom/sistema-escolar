<?php

namespace App\Notifications;

use App\Models\Licencia;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notificación en el sistema (canal database) cuando una licencia
 * cambia de estado (aprobada o rechazada).
 */
class LicenciaResuelta extends Notification
{
    use Queueable;

    public function __construct(public readonly Licencia $licencia) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $aprobada = $this->licencia->estado->value === 'aprobada';
        $estudiante = $this->licencia->estudiante?->user?->nombreCompleto();

        return [
            'tipo' => 'licencia',
            'titulo' => $aprobada ? 'Licencia aprobada' : 'Licencia rechazada',
            'mensaje' => $estudiante
                ? "La licencia de {$estudiante} fue ".($aprobada ? 'aprobada' : 'rechazada').'.'
                : 'Su solicitud de licencia fue '.($aprobada ? 'aprobada' : 'rechazada').'.',
            'licencia_id' => $this->licencia->id,
            'estado' => $this->licencia->estado->value,
            'estado_label' => $this->licencia->estado->label(),
            'motivo_rechazo' => $this->licencia->motivo_rechazo,
            'url' => '/familia/licencias',
        ];
    }
}
