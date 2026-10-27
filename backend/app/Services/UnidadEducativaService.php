<?php

namespace App\Services;

use App\Enums\EstadoUnidad;
use App\Enums\EstadoUsuario;
use App\Enums\Rol;
use App\Enums\TipoUnidad;
use App\Models\DistritoMunicipal;
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
            $territorio = $this->territorio($datos);

            $unidad = UnidadEducativa::create([
                'nombre' => $datos['nombre'],
                'codigo' => $datos['codigo'],
                'direccion' => $datos['direccion'] ?? null,
                'zona' => $datos['zona'] ?? null,
                'macrodistrito_id' => $territorio['macrodistrito_id'],
                'distrito_municipal_id' => $territorio['distrito_municipal_id'],
                'telefono' => $datos['telefono'] ?? null,
                'tipo' => $datos['tipo'] ?? TipoUnidad::FISCAL->value,
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
        $actualizacion = [
            'nombre' => $datos['nombre'],
            'codigo' => $datos['codigo'],
            'direccion' => $datos['direccion'] ?? null,
            'zona' => $datos['zona'] ?? null,
            'telefono' => $datos['telefono'] ?? null,
            'estado' => $datos['estado'] ?? $unidad->estado,
        ];

        if (array_key_exists('tipo', $datos) || array_key_exists('macrodistrito_id', $datos) || array_key_exists('distrito_municipal_id', $datos)) {
            if (array_key_exists('distrito_municipal_id', $datos)) {
                // El distrito es el que manda: el macrodistrito se deriva de él
                // salvo que venga explícito (select dependiente).
                $distrito = $datos['distrito_municipal_id'];
                $macrodistrito = $distrito
                    ? ($datos['macrodistrito_id'] ?? DistritoMunicipal::find($distrito)?->macrodistrito_id)
                    : ($datos['macrodistrito_id'] ?? null);
            } else {
                $distrito = $unidad->distrito_municipal_id;
                $macrodistrito = $datos['macrodistrito_id'] ?? $unidad->macrodistrito_id;
            }

            $actualizacion['macrodistrito_id'] = $macrodistrito;
            $actualizacion['distrito_municipal_id'] = $distrito;
            $actualizacion['tipo'] = ($datos['tipo'] ?? $unidad->tipo?->value) ?? TipoUnidad::FISCAL->value;
        }

        $unidad->update($actualizacion);

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

    /**
     * Resuelve el vínculo territorial: si solo viene el distrito,
     * el macrodistrito se deriva de él.
     *
     * @return array{macrodistrito_id: int|null, distrito_municipal_id: int|null}
     */
    private function territorio(array $datos): array
    {
        $distrito = $datos['distrito_municipal_id'] ?? null;
        $macrodistrito = $datos['macrodistrito_id'] ?? null;

        if ($distrito && ! $macrodistrito) {
            $macrodistrito = DistritoMunicipal::find($distrito)?->macrodistrito_id;
        }

        return [
            'macrodistrito_id' => $macrodistrito ?: null,
            'distrito_municipal_id' => $distrito ?: null,
        ];
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
