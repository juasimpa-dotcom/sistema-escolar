<?php

namespace App\Services;

use App\Enums\EstadoAsistencia;
use App\Enums\EstadoInscripcion;
use App\Enums\EstadoLicencia;
use App\Models\Asistencia;
use App\Models\Licencia;
use App\Support\TenantContext;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    private const UMBRAL_DEFECTO = 51.0;

    private const TTL_SEGUNDOS = 60;

    private const VERSION_KEY = 'dashboard:resumen:version';

    /**
     * Resumen de indicadores del establecimiento.
     *
     * El cálculo es costoso (varias agregaciones), por lo que se cachea
     * durante 60 segundos por unidad/filtros. Se solapa con el staleTime
     * del cliente (React Query).
     *
     * @param  array{fecha?: string, fecha_desde?: string, fecha_hasta?: string, aula_id?: int, umbral?: float}  $filtros
     */
    public function resumen(array $filtros): array
    {
        [$desde, $hasta] = $this->rangoFechas($filtros);
        $clave = $this->claveCache($filtros, $desde, $hasta);

        return Cache::remember($clave, self::TTL_SEGUNDOS, fn () => $this->calcularResumen($filtros, $desde, $hasta));
    }

    /**
     * @param  array<string, mixed>  $filtros
     * @return array<string, mixed>
     */
    private function calcularResumen(array $filtros, string $desde, string $hasta): array
    {
        $unidadId = TenantContext::id();
        $umbral = (float) ($filtros['umbral'] ?? self::UMBRAL_DEFECTO);
        $aulaId = isset($filtros['aula_id']) ? (int) $filtros['aula_id'] : null;

        $totalEstudiantes = $this->totalEstudiantes($unidadId, $aulaId);
        $asistencia = $this->resumenAsistencia($unidadId, $aulaId, $desde, $hasta);
        $licencias = $this->estudiantesConLicencia($unidadId, $aulaId, $desde, $hasta);
        $notasBajas = $this->estudiantesConNotasBajas($unidadId, $aulaId, $umbral);

        $totalRegistros = array_sum($asistencia);
        $presentes = $asistencia[EstadoAsistencia::PRESENTE->value] + $asistencia[EstadoAsistencia::ATRASO->value];
        $porcentajePresentes = $totalRegistros > 0
            ? round(($presentes / $totalRegistros) * 100, 1)
            : 0.0;

        return [
            'rango' => ['desde' => $desde, 'hasta' => $hasta, 'fecha' => $filtros['fecha'] ?? null],
            'umbral_notas' => $umbral,
            'total_estudiantes' => $totalEstudiantes,
            'porcentaje_presentes' => $porcentajePresentes,
            'total_registros_asistencia' => $totalRegistros,
            'con_faltas' => $asistencia[EstadoAsistencia::FALTA->value],
            'con_licencia' => $licencias,
            'con_notas_bajas' => $notasBajas,
            'asistencia_por_estado' => [
                ['estado' => EstadoAsistencia::PRESENTE->value, 'label' => EstadoAsistencia::PRESENTE->label(), 'total' => $asistencia[EstadoAsistencia::PRESENTE->value]],
                ['estado' => EstadoAsistencia::ATRASO->value, 'label' => EstadoAsistencia::ATRASO->label(), 'total' => $asistencia[EstadoAsistencia::ATRASO->value]],
                ['estado' => EstadoAsistencia::FALTA->value, 'label' => EstadoAsistencia::FALTA->label(), 'total' => $asistencia[EstadoAsistencia::FALTA->value]],
                ['estado' => EstadoAsistencia::LICENCIA->value, 'label' => EstadoAsistencia::LICENCIA->label(), 'total' => $asistencia[EstadoAsistencia::LICENCIA->value]],
            ],
        ];
    }

    /**
     * Estudiantes de la unidad (inscritos activos), opcionalmente de un aula.
     */
    private function totalEstudiantes(?int $unidadId, ?int $aulaId): int
    {
        return $this->inscripcionesBase($unidadId, $aulaId)
            ->distinct()
            ->count('inscripciones.estudiante_id');
    }

    /**
     * Conteo de asistencias por estado en el rango.
     *
     * @return array<string, int>
     */
    private function resumenAsistencia(?int $unidadId, ?int $aulaId, string $desde, string $hasta): array
    {
        $conteo = [
            EstadoAsistencia::PRESENTE->value => 0,
            EstadoAsistencia::ATRASO->value => 0,
            EstadoAsistencia::FALTA->value => 0,
            EstadoAsistencia::LICENCIA->value => 0,
        ];

        $filas = Asistencia::query()
            ->when($unidadId !== null, fn ($q) => $q->whereHas('aulaMateria.aula', fn ($sub) => $sub->where('aulas.unidad_educativa_id', $unidadId)))
            ->when($aulaId !== null, fn ($q) => $q->whereHas('aulaMateria', fn ($sub) => $sub->where('aula_materia.aula_id', $aulaId)))
            ->whereDate('asistencias.fecha', '>=', $desde)
            ->whereDate('asistencias.fecha', '<=', $hasta)
            ->selectRaw('estado, COUNT(DISTINCT estudiante_id) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        foreach ($filas as $estado => $total) {
            if (array_key_exists($estado, $conteo)) {
                $conteo[$estado] = (int) $total;
            }
        }

        return $conteo;
    }

    private function estudiantesConLicencia(?int $unidadId, ?int $aulaId, string $desde, string $hasta): int
    {
        $porAsistencia = Asistencia::query()
            ->when($unidadId !== null, fn ($q) => $q->whereHas('aulaMateria.aula', fn ($sub) => $sub->where('aulas.unidad_educativa_id', $unidadId)))
            ->when($aulaId !== null, fn ($q) => $q->whereHas('aulaMateria', fn ($sub) => $sub->where('aula_materia.aula_id', $aulaId)))
            ->whereDate('asistencias.fecha', '>=', $desde)
            ->whereDate('asistencias.fecha', '<=', $hasta)
            ->where('estado', EstadoAsistencia::LICENCIA->value)
            ->distinct()
            ->count('estudiante_id');

        $porLicencia = Licencia::query()
            ->when($unidadId !== null, fn ($q) => $q->whereHas('estudiante', fn ($sub) => $sub->where('estudiantes.unidad_educativa_id', $unidadId)))
            ->when($aulaId !== null, fn ($q) => $q->whereHas('estudiante.inscripciones', fn ($sub) => $sub->where('inscripciones.aula_id', $aulaId)))
            ->where('estado', EstadoLicencia::APROBADA->value)
            ->whereDate('fecha_inicio', '<=', $hasta)
            ->whereDate('fecha_fin', '>=', $desde)
            ->distinct()
            ->count('estudiante_id');

        return max($porAsistencia, $porLicencia);
    }

    /**
     * Estudiantes cuyo promedio de notas (1-100) es menor al umbral.
     */
    private function estudiantesConNotasBajas(?int $unidadId, ?int $aulaId, float $umbral): int
    {
        $estudiantes = $this->inscripcionesBase($unidadId, $aulaId)
            ->join('notas', 'notas.estudiante_id', '=', 'inscripciones.estudiante_id')
            ->join('actividades', 'actividades.id', '=', 'notas.actividad_id')
            ->join('aula_materia', 'aula_materia.id', '=', 'actividades.aula_materia_id')
            ->where('aula_materia.aula_id', DB::raw('inscripciones.aula_id'))
            ->whereNull('actividades.deleted_at')
            ->groupBy('inscripciones.estudiante_id')
            ->havingRaw('AVG(notas.nota) < ?', [$umbral])
            ->selectRaw('inscripciones.estudiante_id')
            ->pluck('inscripciones.estudiante_id');

        return $estudiantes->count();
    }

    private function inscripcionesBase(?int $unidadId, ?int $aulaId): QueryBuilder
    {
        return DB::table('inscripciones')
            ->whereNull('inscripciones.deleted_at')
            ->where('inscripciones.estado', EstadoInscripcion::INSCRITO->value)
            ->join('aulas', 'aulas.id', '=', 'inscripciones.aula_id')
            ->when($unidadId !== null, fn ($q) => $q->where('aulas.unidad_educativa_id', $unidadId))
            ->when($aulaId !== null, fn ($q) => $q->where('inscripciones.aula_id', $aulaId));
    }

    /**
     * Clave de caché estable para el resumen: unidad activa + filtros resueltos.
     *
     * @param  array<string, mixed>  $filtros
     */
    private function claveCache(array $filtros, string $desde, string $hasta): string
    {
        $unidadId = TenantContext::id();
        $version = (int) Cache::get(self::VERSION_KEY, 0);
        $contexto = [
            'v' => $version,
            'unidad' => $unidadId ?? 'all',
            'superadmin' => TenantContext::esSuperadmin(),
            'desde' => $desde,
            'hasta' => $hasta,
            'aula_id' => isset($filtros['aula_id']) ? (int) $filtros['aula_id'] : null,
            'umbral' => (float) ($filtros['umbral'] ?? self::UMBRAL_DEFECTO),
        ];

        return 'dashboard:resumen:'.md5(json_encode($contexto));
    }

    /**
     * Invalida el resumen cacheado (asistencia/licencias/notas mutadas).
     * La versión global hace que las keys anteriores expiren solas por TTL.
     */
    public static function invalidar(): void
    {
        if (! Cache::has(self::VERSION_KEY)) {
            Cache::forever(self::VERSION_KEY, 1);

            return;
        }

        Cache::increment(self::VERSION_KEY);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function rangoFechas(array $filtros): array
    {
        if (! empty($filtros['fecha'])) {
            return [$filtros['fecha'], $filtros['fecha']];
        }

        $hasta = $filtros['fecha_hasta'] ?? now()->toDateString();
        $desde = $filtros['fecha_desde'] ?? now()->startOfMonth()->toDateString();

        return [$desde, $hasta];
    }
}
