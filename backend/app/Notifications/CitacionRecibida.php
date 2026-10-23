<?php

namespace App\Notifications;

use App\Models\Citacion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notificación en el sistema (canal database) cuando llega una citación.
 */
class CitacionRecibida extends Notification
{
    use Queueable;

    public function __construct(public readonly Citacion $citacion) {}

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
        $emisor = $this->citacion->emisor;

        return [
            'tipo' => 'citacion',
            'titulo' => $this->citacion->tipo->label(),
            'mensaje' => $this->citacion->asunto,
            'citacion_id' => $this->citacion->id,
            'emisor' => $emisor?->nombreCompleto(),
            'fecha_hora' => $this->citacion->fecha_hora?->toDateTimeString(),
            'url' => '/familia/citaciones',
        ];
    }
}
