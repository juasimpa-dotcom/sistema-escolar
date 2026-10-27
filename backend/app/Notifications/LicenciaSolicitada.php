<?php

namespace App\Notifications;

use App\Models\Licencia;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** Notifica al Director cuando una familia presenta una solicitud de licencia. */
class LicenciaSolicitada extends Notification
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
        $licencia = $this->licencia;
        $estudiante = $licencia->estudiante?->user?->nombreCompleto() ?? 'un estudiante';
        $padre = $licencia->padre?->nombreCompleto() ?? 'Una familia';

        return [
            'tipo' => 'licencia',
            'titulo' => 'Nueva solicitud de licencia',
            'mensaje' => "{$padre} solicitó licencia para {$estudiante} del {$licencia->fecha_inicio?->format('d/m/Y')} al {$licencia->fecha_fin?->format('d/m/Y')}.",
            'licencia_id' => $licencia->id,
            'estudiante_id' => $licencia->estudiante_id,
            'padre_id' => $licencia->padre_id,
            'url' => '/licencias',
        ];
    }
}
