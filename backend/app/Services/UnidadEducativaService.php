<?php

namespace App\Services;

use App\Enums\EstadoUnidad;
use App\Enums\EstadoUsuario;
use App\Enums\Rol;
use App\Models\UnidadEducativa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UnidadEducativaService
{
    public function __construct(private readonly PersonaService $personas) {}

    /**
     * Crea una unidad educativa junto con su Director.
     */
    public function crear(array $datos): UnidadEducativa
    {
        return DB::transaction(function () use ($datos) {
            $unidad = UnidadEducativa::create([
                'nombre' => $datos['nombre'],
                'codigo' => $datos['codigo'],
                'direccion' => $datos['direccion'] ?? null,
                'telefono' => $datos['telefono'] ?? null,
                'estado' => EstadoUnidad::ACTIVA,
            ]);

            $this->crearDirector($unidad, $datos['director']);

            return $unidad->load('usuarios');
        });
    }

    public function crearDirector(UnidadEducativa $unidad, array $datos): User
    {
        $this->asegurarSinDirector($unidad);

        return $this->personas->crearUsuario($datos, Rol::DIRECTOR, $unidad->id);
    }

    public function actualizar(UnidadEducativa $unidad, array $datos): UnidadEducativa
    {
        $unidad->update([
            'nombre' => $datos['nombre'],
            'codigo' => $datos['codigo'],
            'direccion' => $datos['direccion'] ?? null,
            'telefono' => $datos['telefono'] ?? null,
            'estado' => $datos['estado'] ?? $unidad->estado,
        ]);

        return $unidad->refresh();
    }

    /**
     * Baja lógica de la unidad educativa.
     */
    public function darDeBaja(UnidadEducativa $unidad, string $motivo, string $fecha): UnidadEducativa
    {
        $unidad->update([
            'estado' => EstadoUnidad::INACTIVA,
            'motivo_baja' => $motivo,
            'fecha_baja' => $fecha,
        ]);

        return $unidad->refresh();
    }

    private function asegurarSinDirector(UnidadEducativa $unidad): void
    {
        $existe = User::where('unidad_educativa_id', $unidad->id)
            ->where('rol', Rol::DIRECTOR)
            ->where('estado', EstadoUsuario::ACTIVO)
            ->exists();

        if ($existe) {
            throw ValidationException::withMessages([
                'director' => ['La unidad educativa ya tiene un director activo.'],
            ]);
        }
    }
}
