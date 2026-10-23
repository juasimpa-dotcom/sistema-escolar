<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EstadoCitacion;
use App\Enums\Rol;
use App\Enums\TipoCitacion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Citacion\StoreCitacionRequest;
use App\Http\Resources\CitacionResource;
use App\Models\Citacion;
use App\Models\User;
use App\Services\CitacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CitacionController extends Controller
{
    public function __construct(private readonly CitacionService $service) {}

    /**
     * Bandeja según el rol:
     * - Director/Administrativo: las enviadas por la unidad (filtrable por estado).
     * - Padre/Profesor: las recibidas por el usuario.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Citacion::class);

        $user = $request->user();

        $query = Citacion::query()
            ->select(['id', 'unidad_educativa_id', 'emisor_id', 'destinatario_id', 'estudiante_id', 'tipo', 'asunto', 'mensaje', 'fecha_hora', 'lugar', 'estado', 'leida_en', 'created_at'])
            ->with([
                'emisor:id,nombres,apellidos,rol',
                'destinatario:id,nombres,apellidos',
                'estudiante:id,user_id',
                'estudiante.user:id,nombres,apellidos,ci',
            ]);

        if ($request->filled('bandeja') && $request->string('bandeja')->toString() === 'recibidas') {
            $query->where('destinatario_id', $user->id);
        } elseif ($request->filled('bandeja') && $request->string('bandeja')->toString() === 'enviadas') {
            $query->where('emisor_id', $user->id);
        } elseif (in_array($user->rol, [Rol::DIRECTOR, Rol::ADMINISTRATIVO], true)) {
            $query->where('emisor_id', $user->id);
        } else {
            $query->where('destinatario_id', $user->id);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->string('estado'));
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->string('tipo'));
        }

        $citaciones = $query
            ->orderByDesc('fecha_hora')
            ->orderByDesc('id')
            ->paginate($request->integer('por_pagina', 15));

        return CitacionResource::collection($citaciones);
    }

    public function store(StoreCitacionRequest $request): JsonResponse
    {
        $citacion = $this->service->crear($request->user(), $request->validated());

        return (new CitacionResource($citacion->load([
            'emisor:id,nombres,apellidos,rol',
            'destinatario:id,nombres,apellidos',
            'estudiante:id,user_id',
            'estudiante.user:id,nombres,apellidos,ci',
        ])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Citacion $citacion): CitacionResource
    {
        $this->authorize('view', $citacion);

        return new CitacionResource($citacion->load([
            'emisor:id,nombres,apellidos,rol',
            'destinatario:id,nombres,apellidos',
            'estudiante:id,user_id',
            'estudiante.user:id,nombres,apellidos,ci',
        ]));
    }

    public function marcarVista(Request $request, Citacion $citacion): CitacionResource
    {
        $this->authorize('marcarVista', $citacion);

        $citacion = $this->service->marcarVista($citacion);

        return new CitacionResource($citacion->load([
            'emisor:id,nombres,apellidos,rol',
            'destinatario:id,nombres,apellidos',
            'estudiante:id,user_id',
            'estudiante.user:id,nombres,apellidos,ci',
        ]));
    }

    public function destroy(Request $request, Citacion $citacion): JsonResponse
    {
        $this->authorize('delete', $citacion);

        $citacion->delete();

        return response()->json(['mensaje' => 'Citación anulada.']);
    }

    /**
     * Destinatarios disponibles: padres y profesores de la unidad.
     */
    public function destinatarios(Request $request): JsonResponse
    {
        $this->authorize('create', Citacion::class);

        $user = $request->user();

        $usuarios = User::query()
            ->select(['id', 'unidad_educativa_id', 'rol', 'ci', 'nombres', 'apellidos'])
            ->when($user->unidad_educativa_id !== null, fn ($q) => $q->where('unidad_educativa_id', $user->unidad_educativa_id))
            ->whereIn('rol', [Rol::PADRE, Rol::PROFESOR])
            ->where('estado', 'activo')
            ->with(['hijos:id,user_id', 'hijos.user:id,nombres,apellidos'])
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'ci' => $u->ci,
                'nombre_completo' => $u->nombreCompleto(),
                'rol' => $u->rol->value,
                'rol_label' => $u->rol->label(),
                'hijos' => $u->rol === Rol::PADRE
                    ? $u->hijos->map(fn ($e) => [
                        'id' => $e->id,
                        'nombre_completo' => $e->user?->nombreCompleto(),
                    ])->values()
                    : [],
            ]);

        return response()->json(['data' => $usuarios]);
    }

    public function tipos(): JsonResponse
    {
        $tipos = collect(TipoCitacion::cases())->map(fn (TipoCitacion $t) => [
            'value' => $t->value,
            'label' => $t->label(),
        ]);

        return response()->json(['data' => $tipos, 'estados' => collect(EstadoCitacion::cases())->map(fn (EstadoCitacion $e) => [
            'value' => $e->value,
            'label' => $e->label(),
        ])]);
    }
}
