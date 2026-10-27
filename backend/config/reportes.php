<?php

/*
|--------------------------------------------------------------------------
| Registro de reportes (módulo de reportes)
|--------------------------------------------------------------------------
|
| 'roles' define quién puede verlo y exportarlo; 'filtros' describe los
| controles que la pantalla de filtros debe mostrar para cada reporte.
|
*/

return [
    // ------------------------------------------------------------------
    // Superadmin (visión municipal)
    // ------------------------------------------------------------------
    'matricula-consolidado' => [
        'titulo' => 'Consolidado de matrícula',
        'descripcion' => 'Estudiantes inscritos por unidad educativa, nivel, sexo y rango de edad.',
        'roles' => ['superadmin'],
        'filtros' => ['macrodistrito_id', 'distrito_id', 'unidad_id', 'tipo', 'nivel', 'gestion', 'sexo'],
    ],
    'proyeccion-demanda' => [
        'titulo' => 'Proyección de demanda',
        'descripcion' => 'Matrícula por gestión con tendencia y proyección simple de la siguiente gestión.',
        'roles' => ['superadmin'],
        'filtros' => ['macrodistrito_id', 'distrito_id', 'unidad_id', 'tipo'],
    ],
    'disponibilidad-docente' => [
        'titulo' => 'Disponibilidad docente',
        'descripcion' => 'Maestros activos, plazas ocupadas y vacantes por escuela y materia.',
        'roles' => ['superadmin'],
        'filtros' => ['macrodistrito_id', 'distrito_id', 'unidad_id', 'tipo', 'nivel'],
    ],
    'ausentismo-docente' => [
        'titulo' => 'Ausentismo docente',
        'descripcion' => 'Faltas, atrasos y licencias del personal docente por unidad y rango de fechas.',
        'roles' => ['superadmin'],
        'filtros' => ['macrodistrito_id', 'distrito_id', 'unidad_id', 'tipo', 'desde', 'hasta'],
    ],
    'desercion-reprobacion' => [
        'titulo' => 'Deserción y reprobación',
        'descripcion' => 'Tasas de abandono y reprobación sobre los inscritos, de mayor a menor.',
        'roles' => ['superadmin'],
        'filtros' => ['macrodistrito_id', 'distrito_id', 'unidad_id', 'tipo', 'nivel', 'gestion'],
    ],
    'rendimiento-municipal' => [
        'titulo' => 'Rendimiento académico municipal',
        'descripcion' => 'Promedio general de notas por unidad educativa.',
        'roles' => ['superadmin'],
        'filtros' => ['macrodistrito_id', 'distrito_id', 'unidad_id', 'tipo', 'nivel', 'gestion'],
    ],
    'distribucion-sexo' => [
        'titulo' => 'Mujeres y varones',
        'descripcion' => 'Estudiantes mujeres y varones, y reprobados desglosados por sexo.',
        'roles' => ['superadmin'],
        'filtros' => ['macrodistrito_id', 'distrito_id', 'unidad_id', 'tipo', 'nivel', 'gestion'],
    ],
    'evolucion-asistencia' => [
        'titulo' => 'Evolución de asistencia',
        'descripcion' => 'Evolución de atrasos, faltas y licencias agrupable por día, semana o mes.',
        'roles' => ['superadmin'],
        'filtros' => ['macrodistrito_id', 'distrito_id', 'unidad_id', 'tipo', 'desde', 'hasta', 'agrupar'],
    ],

    // ------------------------------------------------------------------
    // Director (su unidad educativa)
    // ------------------------------------------------------------------
    'asistencia-puntualidad' => [
        'titulo' => 'Asistencia y puntualidad',
        'descripcion' => 'Ausentes y atrasos recurrentes por aula, con resumen diario y mensual.',
        'roles' => ['director'],
        'filtros' => ['desde', 'hasta', 'aula_id', 'umbral'],
    ],
    'ficha-estudiante' => [
        'titulo' => 'Ficha acumulativa del estudiante',
        'descripcion' => 'Historial académico por periodo, disciplinario, asistencia y salud.',
        'roles' => ['director'],
        'filtros' => ['estudiante_id', 'desde', 'hasta'],
    ],
    'boletas-calificaciones' => [
        'titulo' => 'Boletas de calificaciones',
        'descripcion' => 'Libretas por periodo: individual (un estudiante) o masiva (un aula completa).',
        'roles' => ['director'],
        'filtros' => ['periodo_id', 'aula_id', 'estudiante_id'],
    ],
    'alumnos-riesgo' => [
        'titulo' => 'Alumnos en riesgo pedagógico',
        'descripcion' => 'Promedio por debajo del umbral o con resultado reprobado.',
        'roles' => ['director'],
        'filtros' => ['umbral', 'aula_id'],
    ],
    'avance-curricular' => [
        'titulo' => 'Cumplimiento de avance curricular',
        'descripcion' => 'Planificaciones entregadas, clases programadas y dictadas por profesor y materia.',
        'roles' => ['director'],
        'filtros' => ['periodo_id', 'aula_id'],
    ],
    'asistencia-docente' => [
        'titulo' => 'Asistencia y puntualidad docente',
        'descripcion' => 'Resumen del registro manual por profesor y rango de fechas.',
        'roles' => ['director'],
        'filtros' => ['desde', 'hasta'],
    ],
    'estadisticas-finales' => [
        'titulo' => 'Estadísticas de fin de gestión',
        'descripcion' => 'Promovidos, retenidos y abandonos por aula y total, desglosados por sexo.',
        'roles' => ['director'],
        'filtros' => ['gestion', 'aula_id', 'sexo'],
    ],
    'distribucion-sexo-unidad' => [
        'titulo' => 'Mujeres y varones en la unidad',
        'descripcion' => 'Estudiantes mujeres y varones, y reprobados por sexo, por aula.',
        'roles' => ['director'],
        'filtros' => ['gestion', 'nivel', 'sexo'],
    ],
    'evolucion-asistencia-unidad' => [
        'titulo' => 'Evolución de asistencia en la unidad',
        'descripcion' => 'Atrasos, faltas y licencias agrupable por día, semana o mes.',
        'roles' => ['director'],
        'filtros' => ['desde', 'hasta', 'agrupar'],
    ],
];

