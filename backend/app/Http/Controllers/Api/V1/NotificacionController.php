<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    /**
     * Notificaciones en el sistema del usuario autenticado.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = $request->boolean('solo_no_leidas')
            ? $user->unreadNotifications()
            : $user->notifications();

        $notificaciones = $query->orderByDesc('created_at')->limit($request->integer('limite', 30))->get();

        return response()->json([
            'data' => $notificaciones->map(fn ($n) => [
                'id' => $n->id,
                'tipo' => $n->data['tipo'] ?? 'general',
                'titulo' => $n->data['titulo'] ?? 'Notificación',
                'mensaje' => $n->data['mensaje'] ?? '',
                'url' => $n->data['url'] ?? null,
                'datos' => $n->data,
                'leida' => $n->read_at !== null,
                'leida_en' => $n->read_at?->toDateTimeString(),
                'created_at' => $n->created_at?->toDateTimeString(),
            ])->values(),
            'no_leidas' => $user->unreadNotifications()->count(),
        ]);
    }

    public function marcarLeida(Request $request, string $notificacion): JsonResponse
    {
        $registro = $request->user()->notifications()->whereKey($notificacion)->firstOrFail();
        $registro->markAsRead();

        return response()->json(['mensaje' => 'Notificación marcada como leída.']);
    }

    public function marcarTodasLeidas(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['mensaje' => 'Notificaciones marcadas como leídas.']);
    }
}
