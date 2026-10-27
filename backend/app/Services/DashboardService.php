<?php

namespace App\Services;

use App\Enums\DiaSemana;
use App\Enums\EstadoAsistencia;
use App\Enums\EstadoCitacion;
use App\Enums\EstadoInscripcion;
use App\Enums\EstadoLicencia;
use App\Enums\EstadoUsuario;
use App\Enums\NivelEducativo;
use App\Enums\ResultadoInscripcion;
use App\Enums\Rol;
use App\Enums\TipoCitacion;
use App\Models\Actividad;
use App\Models\Asistencia;
use App\Models\AsistenciaDocente;
use App\Models\AulaMateria;
use App\Models\Citacion;
use App\Models\Estudiante;
use App\Models\Gestion;
use App\Models\Horario;
use App\Models\Inscripcion;
use App\Models\Licencia;
use App\Models\Nota;
use App\Models\UnidadEducativa;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    private const UMBRAL_DEFECTO = 51.0;

    private const TTL_SEGUNDOS = 60;

    private const VERSION_KEY = 'dashboard:resumen:version';

    // ------------------------------------------------------------------
    // Tablero de inicio por rol (GET /dashboard)
    // ------------------------------------------------------------------

    /**
     * Resumen del panel de inicio según el rol, cacheado 60 segundos
     * por unidad + usuario + filtros.
     *
     * @param  array<string, mixed>  $filtros
     * @return array{rol: string, generado_en: string, filtros: array<string, mixed>, datos: array<string, mixed>}
     */
    public function tablero(Rol $rol, ?int $usuarioId, array $filtros): array
    {
        $clave = $this->claveCacheTablero($rol, $usuarioId, $filtros);

        return Cache::remember($clave, self::TTL_SEGUNDOS, function () use ($rol, $usuarioId, $filtros) {
            return [
                'rol' => $rol->value,
                'generado_en' => now()->toDateTimeString(),
                'filtros' => $filtros,
                'datos' => $this->resolverTablero($rol, $usuarioId, $filtros),
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $filtros
     * @return array<string, mixed>
     */
    private function resolverTablero(Rol $rol, ?int $usuarioId, array $filtros): array
    {
        return match ($rol) {
            Rol::SUPERADMIN => $this->tableroSuperadmin($filtros),
            Rol::DIRECTOR => $this->tableroDirector($filtros),
            Rol::ADMINISTRATIVO => $this->tableroAdministrativo(),
            Rol::PROFESOR => $this->tableroProfesor($usuarioId),
            Rol::PADRE => $this->tableroPadre($usuarioId),
            Rol::ESTUDIANTE => $this->tableroEstudiante($usuarioId),
            default => [],
        };
    }

    /**
     * Visión municipal: unidades, matrícula y alertas por territorio.
     *
     * @param  array<string, mixed>  $filtros
     * @return array<string, mixed>
     */
    private function tableroSuperadmin(array $filtros): array
    {
        [$macro, $distrito] = $this->territorioFiltrado($filtros);
        $hoy = now()->toDateString();

        $unidades = UnidadEducativa::query()
            ->when($macro || $distrito, fn ($q) => $this->filtrarPorTerritorio($q, $macro, $distrito));

        $estudiantes = Estudiante::query()
            ->where('estado', EstadoUsuario::ACTIVO->value)
            ->when($macro || $distrito, fn ($q) => $q->whereHas('unidadEducativa', fn ($u) => $this->filtrarPorTerritorio($u, $macro, $distrito)));

        $docentes = User::query()
            ->where('rol', Rol::PROFESOR->value)
            ->where('estado', EstadoUsuario::ACTIVO->value)
            ->when($macro || $distrito, fn ($q) => $q->whereHas('unidadEducativa', fn ($u) => $this->filtrarPorTerritorio($u, $macro, $distrito)));

        $porEstado = $this->conteoAsistenciaGlobal($macro, $distrito, $hoy, $hoy);
        $totalHoy = array_sum($porEstado);
        $presentes = $porEstado[EstadoAsistencia::PRESENTE->value] + $porEstado[EstadoAsistencia::ATRASO->value];

        return [
            'filtros_territoriales' => ['macrodistrito_id' => $macro, 'distrito_municipal_id' => $distrito],
            'totales' => [
                'unidades' => $unidades->count(),
                'estudiantes' => $estudiantes->count(),
                'docentes' => $docentes->count(),
            ],
            'asistencia_hoy' => [
                'fecha' => $hoy,
                'total' => $totalHoy,
                'presentes' => $presentes,
                'porcentaje' => $totalHoy > 0 ? round(($presentes / $totalHoy) * 100, 1) : 0.0,
                'por_estado' => $this->arregloPorEstado($porEstado),
            ],
            'matricula_por_macrodistrito' => $this->matriculaPorMacrodistrito($macro, $distrito),
            'estudiantes_por_sexo' => $this->estudiantesPorSexo($macro, $distrito),
            'matricula_por_nivel' => $this->matriculaPorNivel($macro, $distrito),
            'unidades_alerta' => $this->unidadesEnAlerta($macro, $distrito),
            'ultimas_unidades' => $this->ultimasUnidades($macro, $distrito),
        ];
    }

    /**
     * Indicadores de la unidad: asistencia de hoy, alertas y seguimiento.
     *
     * @param  array<string, mixed>  $filtros
     * @return array<string, mixed>
     */
    private function tableroDirector(array $filtros): array
    {
        $unidadId = TenantContext::id();
        $umbral = (float) ($filtros['umbral'] ?? self::UMBRAL_DEFECTO);
        $hoy = now()->toDateString();

        $hoyEstados = $this->resumenAsistencia($unidadId, null, $hoy, $hoy);
        $totalHoy = array_sum($hoyEstados);
        $presentes = $hoyEstados[EstadoAsistencia::PRESENTE->value] + $hoyEstados[EstadoAsistencia::ATRASO->value];

        return [
            'totales' => [
                'estudiantes' => $this->totalEstudiantes($unidadId, null),
                'presentes_hoy' => $presentes,
                'porcentaje_presentes_hoy' => $totalHoy > 0 ? round(($presentes / $totalHoy) * 100, 1) : 0.0,
                'registros_hoy' => $totalHoy,
                'faltas_hoy' => $hoyEstados[EstadoAsistencia::FALTA->value],
                'atrasos_hoy' => $hoyEstados[EstadoAsistencia::ATRASO->value],
                'licencias_hoy' => $this->estudiantesConLicencia($unidadId, null, $hoy, $hoy),
                'notas_bajas' => $this->estudiantesConNotasBajas($unidadId, null, $umbral),
            ],
            'umbral_notas' => $umbral,
            'licencias_pendientes' => $this->licenciasPendientes($unidadId),
            'tendencia' => $this->tendenciaUltimos30Dias($unidadId),
            'riesgo' => $this->alumnosEnRiesgo($unidadId, $umbral),
            'citaciones_recientes' => $this->citacionesRecientes($unidadId),
            'profesores_ausentes' => $this->profesoresAusentesHoy($unidadId),
        ];
    }

    /**
     * Pendientes operativos del administrativo y accesos rápidos.
     *
     * @return array<string, mixed>
     */
    private function tableroAdministrativo(): array
    {
        $unidadId = TenantContext::id();
        $hoy = now()->toDateString();
        $gestion = $this->gestionActual($unidadId);

        $aulasSinProfesor = DB::table('aulas')
            ->leftJoin('aula_materia', 'aula_materia.aula_id', '=', 'aulas.id')
            ->where('aulas.unidad_educativa_id', $unidadId)
            ->whereNull('aulas.deleted_at')
            ->when($gestion, fn ($q) => $q->where('aulas.gestion_id', $gestion->id))
            ->whereNull('aula_materia.id')
            ->select(['aulas.id', 'aulas.grado', 'aulas.paralelo', 'aulas.turno', 'aulas.nivel'])
            ->orderBy('aulas.nivel')
            ->orderBy('aulas.grado')
            ->get();

        $materiasSinHorario = DB::table('materias')
            ->join('aula_materia', 'aula_materia.materia_id', '=', 'materias.id')
            ->leftJoin('horarios', 'horarios.aula_materia_id', '=', 'aula_materia.id')
            ->where('materias.unidad_educativa_id', $unidadId)
            ->whereNull('materias.deleted_at')
            ->whereNull('horarios.id')
            ->distinct()
            ->select(['materias.id', 'materias.nombre'])
            ->orderBy('materias.nombre')
            ->get();

        $actividadesSemana = DB::table('actividades')
            ->join('aula_materia', 'aula_materia.id', '=', 'actividades.aula_materia_id')
            ->join('aulas', 'aulas.id', '=', 'aula_materia.aula_id')
            ->leftJoin('materias', 'materias.id', '=', 'aula_materia.materia_id')
            ->where('aulas.unidad_educativa_id', $unidadId)
            ->whereNull('actividades.deleted_at')
            ->whereDate('actividades.fecha_cumplimiento', '>=', $hoy)
            ->whereDate('actividades.fecha_cumplimiento', '<=', now()->addDays(7)->toDateString())
            ->select([
                'actividades.id', 'actividades.titulo', 'actividades.fecha_cumplimiento as fecha',
                'actividades.fecha_limite_notas', 'aulas.grado', 'aulas.paralelo', 'materias.nombre as materia',
            ])
            ->orderBy('actividades.fecha_cumplimiento')
            ->take(6)
            ->get();

        $sinInscribir = Estudiante::query()
            ->where('unidad_educativa_id', $unidadId)
            ->where('estado', EstadoUsuario::ACTIVO->value)
            ->when($gestion, fn ($q) => $q->whereDoesntHave('inscripciones', fn ($i) => $i
                ->where('gestion_id', $gestion->id)
                ->where('estado', EstadoInscripcion::INSCRITO->value)));

        $citacionesEnviadas = Citacion::where('unidad_educativa_id', $unidadId)->count();
        $citacionesSinLeer = Citacion::where('unidad_educativa_id', $unidadId)
            ->where('estado', EstadoCitacion::ENVIADA->value)
            ->count();

        return [
            'gestion_actual' => $gestion?->anio,
            'aulas_sin_profesor' => [
                'total' => $aulasSinProfesor->count(),
                'items' => $aulasSinProfesor->map(fn ($a) => [
                    'id' => (int) $a->id,
                    'nombre' => trim("{$a->grado} {$a->paralelo}"),
                    'nivel' => $a->nivel,
                    'turno' => $a->turno,
                ])->all(),
            ],
            'materias_sin_horario' => [
                'total' => $materiasSinHorario->count(),
                'items' => $materiasSinHorario->map(fn ($m) => [
                    'id' => (int) $m->id,
                    'nombre' => $m->nombre,
                ])->all(),
            ],
            'actividades_semana' => [
                'total' => $actividadesSemana->count(),
                'items' => $actividadesSemana->map(fn ($a) => [
                    'id' => (int) $a->id,
                    'titulo' => $a->titulo,
                    'fecha' => $a->fecha,
                    'vencida' => $a->fecha < $hoy,
                    'aula' => trim("{$a->grado} {$a->paralelo}"),
                    'materia' => $a->materia,
                ])->all(),
            ],
            'estudiantes_sin_inscribir' => [
                'total' => $sinInscribir->count(),
                'items' => $sinInscribir->with('user:id,nombres,apellidos')->take(5)->get()
                    ->map(fn ($e) => [
                        'id' => $e->id,
                        'nombre' => $e->user?->nombreCompleto() ?? '—',
                    ])->all(),
            ],
            'citaciones' => [
                'enviadas' => $citacionesEnviadas,
                'sin_leer' => $citacionesSinLeer,
                'vistas' => max(0, $citacionesEnviadas - $citacionesSinLeer),
            ],
        ];
    }

    /**
     * Jornada del docente: horario, pendientes y alertas de notas.
     *
     * @return array<string, mixed>
     */
    private function tableroProfesor(?int $usuarioId): array
    {
        $hoy = now()->toDateString();
        $dia = $this->diaDeLaSemana();

        $horarios = $dia
            ? Horario::where('dia', $dia)
                ->whereHas('aulaMateria', fn ($q) => $q->where('profesor_id', $usuarioId))
                ->with(['aulaMateria.aula:id,grado,paralelo,turno,nivel', 'aulaMateria.materia:id,nombre'])
                ->orderBy('hora_inicio')
                ->get()
            : collect();

        $pendientes = $this->pendientesAsistenciaProfesor($usuarioId, $hoy);

        $actividades = Actividad::query()
            ->whereHas('aulaMateria', fn ($q) => $q->where('profesor_id', $usuarioId))
            ->where(function ($q) {
                $desde = now()->subDays(7)->toDateString();
                $hasta = now()->addDays(30)->toDateString();

                $q->whereDate('fecha_limite_notas', '>=', $desde)
                    ->whereDate('fecha_limite_notas', '<=', $hasta)
                    ->orWhere(function ($x) use ($desde, $hasta) {
                        $x->whereNull('fecha_limite_notas')
                            ->whereDate('fecha_cumplimiento', '>=', $desde)
                            ->whereDate('fecha_cumplimiento', '<=', $hasta);
                    });
            })
            ->with(['aulaMateria.aula:id,grado,paralelo', 'aulaMateria.materia:id,nombre'])
            ->get();

        $faltantes = $this->notasFaltantes($actividades);

        $itemsActividades = $actividades
            ->map(function ($a) use ($hoy, $faltantes) {
                $limite = ($a->fecha_limite_notas ?? $a->fecha_cumplimiento)?->toDateString();
                $aulaMateria = $a->aulaMateria;

                return [
                    'id' => $a->id,
                    'titulo' => $a->titulo,
                    'fecha' => $a->fecha_cumplimiento?->toDateString(),
                    'fecha_limite' => $limite,
                    'vencida' => $limite !== null && $limite < $hoy,
                    'aula' => $aulaMateria?->aula ? trim($aulaMateria->aula->grado.' '.$aulaMateria->aula->paralelo) : '—',
                    'materia' => $aulaMateria?->materia?->nombre ?? '—',
                    'notas_faltantes' => $faltantes[$a->id] ?? 0,
                ];
            })
            ->sortBy('fecha_limite')
            ->values()
            ->all();

        $misAulas = DB::table('aula_materia')
            ->join('aulas', 'aulas.id', '=', 'aula_materia.aula_id')
            ->where('aula_materia.profesor_id', $usuarioId)
            ->whereNull('aulas.deleted_at')
            ->distinct()
            ->select(['aulas.id', 'aulas.grado', 'aulas.paralelo', 'aulas.turno', 'aulas.nivel'])
            ->orderBy('aulas.nivel')
            ->orderBy('aulas.grado')
            ->get();

        $aulasPorMateria = DB::table('aula_materia')
            ->where('profesor_id', $usuarioId)
            ->selectRaw('materia_id, COUNT(*) as total')
            ->groupBy('materia_id')
            ->pluck('total', 'materia_id');

        $misMaterias = DB::table('aula_materia')
            ->join('materias', 'materias.id', '=', 'aula_materia.materia_id')
            ->where('aula_materia.profesor_id', $usuarioId)
            ->distinct()
            ->select(['materias.id', 'materias.nombre'])
            ->orderBy('materias.nombre')
            ->get()
            ->map(fn ($m) => [
                'id' => (int) $m->id,
                'nombre' => $m->nombre,
                'aulas' => (int) ($aulasPorMateria[$m->id] ?? 0),
            ]);

        return [
            'dia' => $dia,
            'fecha' => $hoy,
            'horario_hoy' => [
                'total' => $horarios->count(),
                'items' => $horarios->map(fn ($h) => [
                    'id' => $h->id,
                    'hora_inicio' => $h->hora_inicio,
                    'hora_fin' => $h->hora_fin,
                    'aula' => $h->aulaMateria?->aula
                        ? trim($h->aulaMateria->aula->grado.' '.$h->aulaMateria->aula->paralelo)
                        : '—',
                    'materia' => $h->aulaMateria?->materia?->nombre ?? '—',
                ])->all(),
            ],
            'asistencias_pendientes' => [
                'total' => $pendientes->count(),
                'items' => $pendientes->map(fn ($am) => [
                    'aula_materia_id' => $am->id,
                    'aula' => $am->aula ? trim($am->aula->grado.' '.$am->aula->paralelo) : '—',
                    'materia' => $am->materia?->nombre ?? '—',
                ])->all(),
            ],
            'actividades' => [
                'total' => count($itemsActividades),
                'vencidas' => count(array_filter($itemsActividades, fn ($a) => $a['vencida'])),
                'notas_faltantes' => array_sum(array_column($itemsActividades, 'notas_faltantes')),
                'items' => $itemsActividades,
            ],
            'mis_aulas' => [
                'total' => $misAulas->count(),
                'items' => $misAulas->map(fn ($a) => [
                    'id' => (int) $a->id,
                    'nombre' => trim("{$a->grado} {$a->paralelo}"),
                    'nivel' => $a->nivel,
                ])->all(),
            ],
            'mis_materias' => [
                'total' => $misMaterias->count(),
                'items' => $misMaterias->all(),
            ],
        ];
    }

    /**
     * Tarjetas por cada hijo: asistencia del mes, notas y pendientes.
     *
     * @return array<string, mixed>
     */
    private function tableroPadre(?int $usuarioId): array
    {
        $padre = User::find($usuarioId);

        if (! $padre) {
            return ['totales' => ['hijos' => 0, 'citaciones_sin_leer' => 0, 'licencias_en_curso' => 0], 'hijos' => []];
        }

        $hijos = $padre->hijos()->with('user:id,nombres,apellidos')->take(10)->get();
        $tarjetas = [];

        foreach ($hijos as $hijo) {
            $tarjetas[] = $this->tarjetaHijo($padre->id, $hijo);
        }

        return [
            'totales' => [
                'hijos' => count($tarjetas),
                'citaciones_sin_leer' => array_sum(array_column($tarjetas, 'citaciones_sin_leer')),
                'licencias_en_curso' => array_sum(array_column($tarjetas, 'licencias_en_curso')),
            ],
            'hijos' => $tarjetas,
        ];
    }

    /**
     * Panel del estudiante: jornada, actividades y notas.
     *
     * @return array<string, mixed>
     */
    private function tableroEstudiante(?int $usuarioId): array
    {
        $estudiante = Estudiante::where('user_id', $usuarioId)->first();

        if (! $estudiante) {
            return [
                'dia' => null,
                'horario_hoy' => ['total' => 0, 'items' => []],
                'proximas_actividades' => ['total' => 0, 'items' => []],
                'ultimas_notas' => ['total' => 0, 'items' => []],
                'asistencia_mes' => $this->asistenciaVacia(),
            ];
        }

        $hoy = now()->toDateString();
        $dia = $this->diaDeLaSemana();

        $inscripcion = Inscripcion::query()
            ->where('estudiante_id', $estudiante->id)
            ->where('estado', EstadoInscripcion::INSCRITO->value)
            ->orderByDesc('gestion_id')
            ->with('aula:id,grado,paralelo,turno,nivel')
            ->first();

        $aulaId = $inscripcion?->aula_id;

        $horarios = ($dia && $aulaId)
            ? Horario::where('dia', $dia)
                ->whereHas('aulaMateria', fn ($q) => $q->where('aula_id', $aulaId))
                ->with(['aulaMateria.materia:id,nombre', 'aulaMateria.profesor:id,nombres,apellidos'])
                ->orderBy('hora_inicio')
                ->get()
            : collect();

        $proximas = $aulaId
            ? Actividad::query()
                ->whereHas('aulaMateria', fn ($q) => $q->where('aula_id', $aulaId))
                ->whereDate('fecha_cumplimiento', '>=', $hoy)
                ->with('aulaMateria.materia:id,nombre')
                ->orderBy('fecha_cumplimiento')
                ->take(5)
                ->get()
            : collect();

        $conNota = Nota::where('estudiante_id', $estudiante->id)
            ->whereIn('actividad_id', $proximas->pluck('id'))
            ->pluck('actividad_id')
            ->all();

        $ultimasNotas = Nota::where('estudiante_id', $estudiante->id)
            ->with('actividad:id,titulo,puntaje_maximo')
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        return [
            'dia' => $dia,
            'fecha' => $hoy,
            'aula' => $inscripcion?->aula
                ? trim($inscripcion->aula->grado.' '.$inscripcion->aula->paralelo)
                : null,
            'horario_hoy' => [
                'total' => $horarios->count(),
                'items' => $horarios->map(fn ($h) => [
                    'id' => $h->id,
                    'hora_inicio' => $h->hora_inicio,
                    'hora_fin' => $h->hora_fin,
                    'materia' => $h->aulaMateria?->materia?->nombre ?? '—',
                    'profesor' => $h->aulaMateria?->profesor?->nombreCompleto() ?? '—',
                ])->all(),
            ],
            'proximas_actividades' => [
                'total' => $proximas->count(),
                'items' => $proximas->map(fn ($a) => [
                    'id' => $a->id,
                    'titulo' => $a->titulo,
                    'fecha' => $a->fecha_cumplimiento?->toDateString(),
                    'materia' => $a->aulaMateria?->materia?->nombre ?? '—',
                    'sin_nota' => ! in_array($a->id, $conNota, true),
                ])->all(),
            ],
            'ultimas_notas' => [
                'total' => $ultimasNotas->count(),
                'items' => $ultimasNotas->map(fn ($n) => [
                    'actividad' => $n->actividad?->titulo ?? '—',
                    'nota' => (float) $n->nota,
                    'puntaje_maximo' => $n->actividad?->puntaje_maximo,
                    'fecha' => $n->created_at?->toDateString(),
                ])->all(),
            ],
            'asistencia_mes' => $this->asistenciaDelMes($estudiante->id),
        ];
    }

    // ------------------------------------------------------------------
    // Agregados del superadmin
    // ------------------------------------------------------------------

    /**
     * @return array<int, array{codigo: string, nombre: string, total: int}>
     */
    private function matriculaPorMacrodistrito(?int $macro, ?int $distrito): array
    {
        return DB::table('inscripciones')
            ->join('aulas', 'aulas.id', '=', 'inscripciones.aula_id')
            ->join('unidades_educativas', 'unidades_educativas.id', '=', 'aulas.unidad_educativa_id')
            ->leftJoin('macrodistritos', 'macrodistritos.id', '=', 'unidades_educativas.macrodistrito_id')
            ->when($macro, fn ($q) => $q->where('macrodistritos.id', $macro))
            ->when($distrito, fn ($q) => $q->where('unidades_educativas.distrito_municipal_id', $distrito))
            ->whereNull('inscripciones.deleted_at')
            ->where('inscripciones.estado', EstadoInscripcion::INSCRITO->value)
            ->selectRaw("COALESCE(macrodistritos.codigo, 'SD') as codigo, COALESCE(macrodistritos.nombre, 'Sin asignar') as nombre, COUNT(DISTINCT inscripciones.estudiante_id) as total")
            ->groupByRaw("COALESCE(macrodistritos.codigo, 'SD'), COALESCE(macrodistritos.nombre, 'Sin asignar')")
            ->orderBy('codigo')
            ->get()
            ->map(fn ($f) => ['codigo' => $f->codigo, 'nombre' => $f->nombre, 'total' => (int) $f->total])
            ->all();
    }

    /**
     * @return array<int, array{clave: string, label: string, total: int}>
     */
    private function estudiantesPorSexo(?int $macro, ?int $distrito): array
    {
        return DB::table('estudiantes')
            ->join('users', 'users.id', '=', 'estudiantes.user_id')
            ->join('unidades_educativas', 'unidades_educativas.id', '=', 'estudiantes.unidad_educativa_id')
            ->when($macro, fn ($q) => $q->where('unidades_educativas.macrodistrito_id', $macro))
            ->when($distrito, fn ($q) => $q->where('unidades_educativas.distrito_municipal_id', $distrito))
            ->whereNull('estudiantes.deleted_at')
            ->where('estudiantes.estado', EstadoUsuario::ACTIVO->value)
            ->selectRaw("COALESCE(users.sexo, '') as sexo, COUNT(*) as total")
            ->groupBy('users.sexo')
            ->get()
            ->map(fn ($f) => [
                'clave' => $f->sexo !== '' ? $f->sexo : 'N',
                'label' => match ($f->sexo) {
                    'F' => 'Mujeres',
                    'M' => 'Varones',
                    default => 'Sin dato',
                },
                'total' => (int) $f->total,
            ])
            ->all();
    }

    /**
     * @return array<int, array{nivel: string, label: string, total: int}>
     */
    private function matriculaPorNivel(?int $macro, ?int $distrito): array
    {
        return DB::table('inscripciones')
            ->join('aulas', 'aulas.id', '=', 'inscripciones.aula_id')
            ->join('unidades_educativas', 'unidades_educativas.id', '=', 'aulas.unidad_educativa_id')
            ->when($macro, fn ($q) => $q->where('unidades_educativas.macrodistrito_id', $macro))
            ->when($distrito, fn ($q) => $q->where('unidades_educativas.distrito_municipal_id', $distrito))
            ->whereNull('inscripciones.deleted_at')
            ->where('inscripciones.estado', EstadoInscripcion::INSCRITO->value)
            ->selectRaw('aulas.nivel as nivel, COUNT(DISTINCT inscripciones.estudiante_id) as total')
            ->groupBy('aulas.nivel')
            ->orderBy('aulas.nivel')
            ->get()
            ->map(fn ($f) => [
                'nivel' => $f->nivel,
                'label' => NivelEducativo::tryFrom($f->nivel)?->label() ?? $f->nivel,
                'total' => (int) $f->total,
            ])
            ->all();
    }

    /**
     * Top 5 de unidades con más faltas (30 días) o abandonos.
     *
     * @return array<int, array<string, mixed>>
     */
    private function unidadesEnAlerta(?int $macro, ?int $distrito): array
    {
        $filtroTerritorial = function ($q) use ($macro, $distrito) {
            $q->join('unidades_educativas', 'unidades_educativas.id', '=', 'aulas.unidad_educativa_id')
                ->when($macro, fn ($x) => $x->where('unidades_educativas.macrodistrito_id', $macro))
                ->when($distrito, fn ($x) => $x->where('unidades_educativas.distrito_municipal_id', $distrito));
        };

        $faltas = DB::table('asistencias')
            ->join('aula_materia', 'aula_materia.id', '=', 'asistencias.aula_materia_id')
            ->join('aulas', 'aulas.id', '=', 'aula_materia.aula_id')
            ->when($macro || $distrito, $filtroTerritorial)
            ->where('asistencias.estado', EstadoAsistencia::FALTA->value)
            ->whereDate('asistencias.fecha', '>=', now()->subDays(30)->toDateString())
            ->selectRaw('aulas.unidad_educativa_id as unidad_id, COUNT(*) as total')
            ->groupBy('aulas.unidad_educativa_id')
            ->pluck('total', 'unidad_id');

        $abandonos = DB::table('inscripciones')
            ->join('aulas', 'aulas.id', '=', 'inscripciones.aula_id')
            ->when($macro || $distrito, $filtroTerritorial)
            ->whereNull('inscripciones.deleted_at')
            ->where('inscripciones.resultado_final', ResultadoInscripcion::ABANDONO->value)
            ->selectRaw('aulas.unidad_educativa_id as unidad_id, COUNT(*) as total')
            ->groupBy('aulas.unidad_educativa_id')
            ->pluck('total', 'unidad_id');

        $ids = $faltas->keys()->union($abandonos->keys())
            ->sortBy(fn ($id) => -((int) ($faltas[$id] ?? 0) + (int) ($abandonos[$id] ?? 0)))
            ->take(5)
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $unidades = UnidadEducativa::with('macrodistrito:id,codigo,nombre')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return $ids
            ->map(fn ($id) => $unidades->get($id))
            ->filter()
            ->map(fn ($u) => [
                'id' => $u->id,
                'nombre' => $u->nombre,
                'codigo' => $u->codigo,
                'macrodistrito' => $u->macrodistrito?->codigo,
                'faltas' => (int) ($faltas[$u->id] ?? 0),
                'abandono' => (int) ($abandonos[$u->id] ?? 0),
                'alerta' => (int) ($faltas[$u->id] ?? 0) + (int) ($abandonos[$u->id] ?? 0),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function ultimasUnidades(?int $macro, ?int $distrito): array
    {
        return UnidadEducativa::query()
            ->when($macro || $distrito, fn ($q) => $this->filtrarPorTerritorio($q, $macro, $distrito))
            ->with('macrodistrito:id,codigo,nombre')
            ->orderByDesc('created_at')
            ->take(5)
            ->get(['id', 'nombre', 'codigo', 'tipo', 'zona', 'macrodistrito_id', 'created_at'])
            ->map(fn ($u) => [
                'id' => $u->id,
                'nombre' => $u->nombre,
                'codigo' => $u->codigo,
                'tipo' => $u->tipo?->value,
                'zona' => $u->zona,
                'macrodistrito' => $u->macrodistrito?->codigo,
                'creada' => $u->created_at?->toDateString(),
            ])
            ->all();
    }

    // ------------------------------------------------------------------
    // Agregados del director
    // ------------------------------------------------------------------

    /**
     * @return array{total: int, items: array<int, array<string, mixed>>}
     */
    private function licenciasPendientes(?int $unidadId): array
    {
        $query = Licencia::query()
            ->when($unidadId !== null, fn ($q) => $q->whereHas('estudiante', fn ($e) => $e->where('estudiantes.unidad_educativa_id', $unidadId)))
            ->where('estado', EstadoLicencia::PENDIENTE->value);

        $total = (clone $query)->count();

        $items = $query
            ->with(['estudiante:id,user_id,unidad_educativa_id', 'estudiante.user:id,nombres,apellidos'])
            ->orderByDesc('fecha_inicio')
            ->take(5)
            ->get()
            ->map(fn ($l) => [
                'id' => $l->id,
                'estudiante' => $l->estudiante?->user?->nombreCompleto() ?? '—',
                'fecha_inicio' => $l->fecha_inicio?->toDateString(),
                'fecha_fin' => $l->fecha_fin?->toDateString(),
                'motivo' => $l->motivo,
            ])
            ->all();

        return ['total' => $total, 'items' => $items];
    }

    /**
     * Serie diaria de faltas y atrasos de los últimos 30 días.
     *
     * @return array<int, array{fecha: string, faltas: int, atrasos: int}>
     */
    private function tendenciaUltimos30Dias(?int $unidadId): array
    {
        $desde = now()->subDays(29)->toDateString();

        $filas = DB::table('asistencias')
            ->join('aula_materia', 'aula_materia.id', '=', 'asistencias.aula_materia_id')
            ->join('aulas', 'aulas.id', '=', 'aula_materia.aula_id')
            ->where('aulas.unidad_educativa_id', $unidadId)
            ->whereDate('asistencias.fecha', '>=', $desde)
            ->whereDate('asistencias.fecha', '<=', now()->toDateString())
            ->whereIn('asistencias.estado', [EstadoAsistencia::FALTA->value, EstadoAsistencia::ATRASO->value])
            ->selectRaw('asistencias.fecha as fecha, asistencias.estado as estado, COUNT(DISTINCT asistencias.estudiante_id) as total')
            ->groupBy('asistencias.fecha', 'asistencias.estado')
            ->get();

        $porFecha = $filas->groupBy(fn ($f) => substr((string) $f->fecha, 0, 10));
        $serie = [];

        for ($i = 29; $i >= 0; $i--) {
            $fecha = now()->subDays($i)->toDateString();
            $dia = $porFecha->get($fecha, collect());

            $serie[] = [
                'fecha' => $fecha,
                'faltas' => (int) ($dia->firstWhere('estado', EstadoAsistencia::FALTA->value)->total ?? 0),
                'atrasos' => (int) ($dia->firstWhere('estado', EstadoAsistencia::ATRASO->value)->total ?? 0),
            ];
        }

        return $serie;
    }

    /**
     * Top 5 de alumnos con promedio menor al umbral.
     *
     * @return array{total: int, items: array<int, array<string, mixed>>}
     */
    private function alumnosEnRiesgo(?int $unidadId, float $umbral): array
    {
        $consulta = fn (): QueryBuilder => DB::table('estudiantes')
            ->join('users', 'users.id', '=', 'estudiantes.user_id')
            ->join('inscripciones', 'inscripciones.estudiante_id', '=', 'estudiantes.id')
            ->join('aulas', 'aulas.id', '=', 'inscripciones.aula_id')
            ->join('notas', 'notas.estudiante_id', '=', 'estudiantes.id')
            ->join('actividades', 'actividades.id', '=', 'notas.actividad_id')
            ->join('aula_materia', 'aula_materia.id', '=', 'actividades.aula_materia_id')
            ->where('aulas.unidad_educativa_id', $unidadId)
            ->whereColumn('aula_materia.aula_id', 'aulas.id')
            ->whereNull('estudiantes.deleted_at')
            ->whereNull('inscripciones.deleted_at')
            ->whereNull('actividades.deleted_at')
            ->where('inscripciones.estado', EstadoInscripcion::INSCRITO->value)
            ->groupBy('estudiantes.id', 'users.nombres', 'users.apellidos', 'aulas.grado', 'aulas.paralelo')
            ->havingRaw('AVG(notas.nota) < '.sprintf('%.2F', $umbral));

        // Se cuenta en PHP: con GROUP BY + HAVING, count() de Laravel
        // devolvería el conteo del primer grupo, no de todos los grupos.
        $total = $consulta()->select('estudiantes.id')->get()->count();

        $items = $consulta()
            ->selectRaw('estudiantes.id as estudiante_id, users.nombres, users.apellidos, aulas.grado, aulas.paralelo, ROUND(AVG(notas.nota), 1) as promedio')
            ->orderBy('promedio')
            ->take(5)
            ->get()
            ->map(fn ($f) => [
                'estudiante_id' => (int) $f->estudiante_id,
                'nombre' => trim("{$f->nombres} {$f->apellidos}"),
                'aula' => trim("{$f->grado} {$f->paralelo}"),
                'promedio' => (float) $f->promedio,
            ])
            ->all();

        return ['total' => $total, 'items' => $items];
    }

    /**
     * @return array{total: int, items: array<int, array<string, mixed>>}
     */
    private function citacionesRecientes(?int $unidadId): array
    {
        $total = Citacion::where('unidad_educativa_id', $unidadId)->count();

        $items = Citacion::where('unidad_educativa_id', $unidadId)
            ->with(['destinatario:id,nombres,apellidos', 'estudiante:id,user_id', 'estudiante.user:id,nombres,apellidos'])
            ->orderByDesc('fecha_hora')
            ->take(5)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'asunto' => $c->asunto,
                'tipo' => TipoCitacion::tryFrom((string) $c->tipo)?->label() ?? $c->tipo,
                'fecha_hora' => $c->fecha_hora?->toDateTimeString(),
                'estado' => $c->estado,
                'destinatario' => $c->destinatario?->nombreCompleto() ?? '—',
            ])
            ->all();

        return ['total' => $total, 'items' => $items];
    }

    /**
     * @return array{total: int, items: array<int, array<string, mixed>>}
     */
    private function profesoresAusentesHoy(?int $unidadId): array
    {
        $query = AsistenciaDocente::query()
            ->when($unidadId !== null, fn ($q) => $q->where('unidad_educativa_id', $unidadId))
            ->whereDate('fecha', now()->toDateString())
            ->whereIn('estado', [EstadoAsistencia::FALTA->value, EstadoAsistencia::LICENCIA->value]);

        $total = (clone $query)->count();

        $items = $query
            ->with('profesor:id,nombres,apellidos')
            ->orderBy('estado')
            ->take(6)
            ->get()
            ->map(fn ($a) => [
                'nombre' => $a->profesor?->nombreCompleto() ?? '—',
                'estado' => $a->estado,
                'observacion' => $a->observacion,
            ])
            ->all();

        return ['total' => $total, 'items' => $items];
    }

    // ------------------------------------------------------------------
    // Agregados de familia y estudiante
    // ------------------------------------------------------------------

    /**
     * Tarjeta de resumen de un hijo para el padre de familia.
     *
     * @return array<string, mixed>
     */
    private function tarjetaHijo(int $padreId, Estudiante $hijo): array
    {
        $hoy = now()->toDateString();

        $inscripcion = Inscripcion::query()
            ->where('estudiante_id', $hijo->id)
            ->where('estado', EstadoInscripcion::INSCRITO->value)
            ->orderByDesc('gestion_id')
            ->with('aula:id,grado,paralelo,turno,nivel')
            ->first();

        $aula = $inscripcion?->aula;

        $actividadesPendientes = $aula
            ? Actividad::whereHas('aulaMateria', fn ($q) => $q->where('aula_id', $aula->id))
                ->whereDate('fecha_cumplimiento', '>=', now()->subDays(30)->toDateString())
                ->whereDoesntHave('notas', fn ($q) => $q->where('estudiante_id', $hijo->id))
                ->count()
            : 0;

        return [
            'estudiante_id' => $hijo->id,
            'nombre' => $hijo->user?->nombreCompleto() ?? '—',
            'aula' => $aula ? trim($aula->grado.' '.$aula->paralelo) : 'Sin aula',
            'nivel' => $aula?->nivel,
            'asistencia_mes' => $this->asistenciaDelMes($hijo->id),
            'ultimas_notas' => $this->ultimasNotas($hijo->id, 3),
            'actividades_pendientes' => $actividadesPendientes,
            'licencias_en_curso' => Licencia::where('estudiante_id', $hijo->id)
                ->whereIn('estado', [EstadoLicencia::PENDIENTE->value, EstadoLicencia::APROBADA->value])
                ->whereDate('fecha_inicio', '<=', $hoy)
                ->whereDate('fecha_fin', '>=', $hoy)
                ->count(),
            'citaciones_sin_leer' => Citacion::where('destinatario_id', $padreId)
                ->where('estudiante_id', $hijo->id)
                ->where('estado', EstadoCitacion::ENVIADA->value)
                ->count(),
        ];
    }

    /**
     * Asistencia del mes en curso de un estudiante.
     *
     * @return array<string, mixed>
     */
    private function asistenciaDelMes(int $estudianteId): array
    {
        $conteo = DB::table('asistencias')
            ->where('estudiante_id', $estudianteId)
            ->whereDate('fecha', '>=', now()->startOfMonth()->toDateString())
            ->whereDate('fecha', '<=', now()->toDateString())
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return [
            ...$this->resumenDesdeConteo($conteo->all()),
            'licencias_aprobadas' => Licencia::query()
                ->where('estudiante_id', $estudianteId)
                ->where('estado', EstadoLicencia::APROBADA->value)
                ->count(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function ultimasNotas(int $estudianteId, int $cantidad): array
    {
        return Nota::where('estudiante_id', $estudianteId)
            ->with('actividad:id,titulo,puntaje_maximo')
            ->orderByDesc('created_at')
            ->take($cantidad)
            ->get()
            ->map(fn ($n) => [
                'actividad' => $n->actividad?->titulo ?? '—',
                'nota' => (float) $n->nota,
                'puntaje_maximo' => $n->actividad?->puntaje_maximo,
                'fecha' => $n->created_at?->toDateString(),
            ])
            ->all();
    }

    private function asistenciaVacia(): array
    {
        return $this->resumenDesdeConteo([]);
    }

    // ------------------------------------------------------------------
    // Auxiliares compartidos
    // ------------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $conteo
     * @return array<string, mixed>
     */
    private function resumenDesdeConteo(array $conteo): array
    {
        $total = array_sum($conteo);
        $presentes = (int) ($conteo[EstadoAsistencia::PRESENTE->value] ?? 0)
            + (int) ($conteo[EstadoAsistencia::ATRASO->value] ?? 0);

        return [
            'total' => $total,
            'presentes' => $presentes,
            'faltas' => (int) ($conteo[EstadoAsistencia::FALTA->value] ?? 0),
            'licencias' => (int) ($conteo[EstadoAsistencia::LICENCIA->value] ?? 0),
            'licencias_aprobadas' => 0,
            'atrasos' => (int) ($conteo[EstadoAsistencia::ATRASO->value] ?? 0),
            'porcentaje' => $total > 0 ? round(($presentes / $total) * 100, 1) : 0.0,
        ];
    }

    /**
     * Aulas sin asistencia tomada hoy para un docente.
     */
    private function pendientesAsistenciaProfesor(?int $profesorId, string $hoy): Collection
    {
        return AulaMateria::query()
            ->where('profesor_id', $profesorId)
            ->whereDoesntHave('asistencias', fn ($q) => $q->whereDate('fecha', $hoy))
            ->with(['aula:id,grado,paralelo,turno,nivel', 'materia:id,nombre'])
            ->orderBy('aula_id')
            ->get();
    }

    /**
     * Notas que faltan por cargar en cada actividad (inscritos sin nota).
     *
     * @param  Collection<int, Actividad>  $actividades
     * @return array<int, int>
     */
    private function notasFaltantes(Collection $actividades): array
    {
        if ($actividades->isEmpty()) {
            return [];
        }

        $ids = $actividades->pluck('id');
        $aulaIds = $actividades->map(fn ($a) => $a->aulaMateria?->aula_id)->filter()->unique()->values();

        $notas = DB::table('notas')
            ->whereIn('actividad_id', $ids)
            ->selectRaw('actividad_id, COUNT(*) as total')
            ->groupBy('actividad_id')
            ->pluck('total', 'actividad_id');

        $inscritos = DB::table('inscripciones')
            ->whereNull('inscripciones.deleted_at')
            ->where('inscripciones.estado', EstadoInscripcion::INSCRITO->value)
            ->whereIn('inscripciones.aula_id', $aulaIds)
            ->selectRaw('aula_id, COUNT(DISTINCT estudiante_id) as total')
            ->groupBy('aula_id')
            ->pluck('total', 'aula_id');

        $resultado = [];

        foreach ($actividades as $actividad) {
            $aulaId = $actividad->aulaMateria?->aula_id;
            $inscritosAula = (int) ($inscritos[$aulaId] ?? 0);
            $cargadas = (int) ($notas[$actividad->id] ?? 0);
            $resultado[$actividad->id] = max(0, $inscritosAula - $cargadas);
        }

        return $resultado;
    }

    /**
     * Conteo de asistencia por estado (todas las unidades o filtrado por territorio).
     *
     * @return array<string, int>
     */
    private function conteoAsistenciaGlobal(?int $macro, ?int $distrito, string $desde, string $hasta): array
    {
        $filas = DB::table('asistencias')
            ->join('aula_materia', 'aula_materia.id', '=', 'asistencias.aula_materia_id')
            ->join('aulas', 'aulas.id', '=', 'aula_materia.aula_id')
            ->when($macro || $distrito, fn ($q) => $q
                ->join('unidades_educativas', 'unidades_educativas.id', '=', 'aulas.unidad_educativa_id')
                ->when($macro, fn ($x) => $x->where('unidades_educativas.macrodistrito_id', $macro))
                ->when($distrito, fn ($x) => $x->where('unidades_educativas.distrito_municipal_id', $distrito)))
            ->whereDate('asistencias.fecha', '>=', $desde)
            ->whereDate('asistencias.fecha', '<=', $hasta)
            ->selectRaw('asistencias.estado, COUNT(DISTINCT asistencias.estudiante_id) as total')
            ->groupBy('asistencias.estado')
            ->pluck('total', 'estado');

        $resultado = [];

        foreach ([EstadoAsistencia::PRESENTE, EstadoAsistencia::ATRASO, EstadoAsistencia::FALTA, EstadoAsistencia::LICENCIA] as $estado) {
            $resultado[$estado->value] = (int) ($filas[$estado->value] ?? 0);
        }

        return $resultado;
    }

    /**
     * @param  array<string, int>  $conteo
     * @return array<int, array{estado: string, label: string, total: int}>
     */
    private function arregloPorEstado(array $conteo): array
    {
        return collect([EstadoAsistencia::PRESENTE, EstadoAsistencia::ATRASO, EstadoAsistencia::FALTA, EstadoAsistencia::LICENCIA])
            ->map(fn ($estado) => [
                'estado' => $estado->value,
                'label' => $estado->label(),
                'total' => (int) ($conteo[$estado->value] ?? 0),
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filtros
     * @return array{0: int|null, 1: int|null}
     */
    private function territorioFiltrado(array $filtros): array
    {
        $macro = ! empty($filtros['macrodistrito_id']) ? (int) $filtros['macrodistrito_id'] : null;
        $distrito = ! empty($filtros['distrito_municipal_id']) ? (int) $filtros['distrito_municipal_id'] : null;

        return [$macro, $distrito];
    }

    private function filtrarPorTerritorio($query, ?int $macro, ?int $distrito): void
    {
        $query->when($macro, fn ($q) => $q->where('macrodistrito_id', $macro))
            ->when($distrito, fn ($q) => $q->where('distrito_municipal_id', $distrito));
    }

    private function gestionActual(?int $unidadId): ?Gestion
    {
        if (! $unidadId) {
            return null;
        }

        return Gestion::where('unidad_educativa_id', $unidadId)
            ->where('es_actual', true)
            ->first()
            ?? Gestion::where('unidad_educativa_id', $unidadId)->orderByDesc('anio')->first();
    }

    private function diaDeLaSemana(): ?string
    {
        return match (now()->dayOfWeek) {
            1 => DiaSemana::LUNES->value,
            2 => DiaSemana::MARTES->value,
            3 => DiaSemana::MIERCOLES->value,
            4 => DiaSemana::JUEVES->value,
            5 => DiaSemana::VIERNES->value,
            6 => DiaSemana::SABADO->value,
            default => null,
        };
    }

    /**
     * Clave de caché del tablero: versión + rol + unidad + usuario + filtros.
     *
     * @param  array<string, mixed>  $filtros
     */
    private function claveCacheTablero(Rol $rol, ?int $usuarioId, array $filtros): string
    {
        $version = (int) Cache::get(self::VERSION_KEY, 0);

        $contexto = [
            'v' => $version,
            'rol' => $rol->value,
            'unidad' => TenantContext::id() ?? 'all',
            'usuario' => $usuarioId,
            'filtros' => $filtros,
        ];

        return 'dashboard:tablero:'.md5(json_encode($contexto));
    }

    // ------------------------------------------------------------------
    // Resumen histórico (GET /dashboard/resumen)
    // ------------------------------------------------------------------

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
            'asistencia_por_estado' => $this->arregloPorEstado($asistencia),
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
            ->havingRaw('AVG(notas.nota) < '.sprintf('%.2F', $umbral))
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
