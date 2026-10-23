<?php

use App\Http\Controllers\Api\V1\ActividadController;
use App\Http\Controllers\Api\V1\AsistenciaController;
use App\Http\Controllers\Api\V1\AulaController;
use App\Http\Controllers\Api\V1\AulaMateriaController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\CitacionController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\EstudianteController;
use App\Http\Controllers\Api\V1\GestionController;
use App\Http\Controllers\Api\V1\HorarioController;
use App\Http\Controllers\Api\V1\InscripcionController;
use App\Http\Controllers\Api\V1\LicenciaController;
use App\Http\Controllers\Api\V1\MateriaController;
use App\Http\Controllers\Api\V1\NotaController;
use App\Http\Controllers\Api\V1\NotificacionController;
use App\Http\Controllers\Api\V1\PersonaController;
use App\Http\Controllers\Api\V1\PortalController;
use App\Http\Controllers\Api\V1\UnidadEducativaController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
    });

    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
        Route::prefix('auth')->group(function () {
            Route::get('me', [AuthController::class, 'me']);
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('cambiar-contrasena', [AuthController::class, 'cambiarContrasena']);
        });

        // Unidades educativas (Superadmin)
        Route::get('unidades', [UnidadEducativaController::class, 'index']);
        Route::post('unidades', [UnidadEducativaController::class, 'store']);
        Route::get('unidades/{unidad}', [UnidadEducativaController::class, 'show']);
        Route::put('unidades/{unidad}', [UnidadEducativaController::class, 'update']);
        Route::post('unidades/{unidad}/baja', [UnidadEducativaController::class, 'darDeBaja']);

        // Personas: profesores, administrativos y padres
        Route::get('personas', [PersonaController::class, 'index']);
        Route::post('personas', [PersonaController::class, 'store']);
        Route::get('personas/{persona}', [PersonaController::class, 'show']);
        Route::put('personas/{persona}', [PersonaController::class, 'update']);
        Route::post('personas/{persona}/baja', [PersonaController::class, 'darDeBaja']);

        // Estudiantes
        Route::get('mis-hijos', [EstudianteController::class, 'misHijos']);
        Route::get('estudiantes', [EstudianteController::class, 'index']);
        Route::post('estudiantes', [EstudianteController::class, 'store']);
        Route::get('estudiantes/{estudiante}', [EstudianteController::class, 'show']);
        Route::put('estudiantes/{estudiante}', [EstudianteController::class, 'update']);
        Route::post('estudiantes/{estudiante}/baja', [EstudianteController::class, 'darDeBaja']);

        // Académico: gestiones
        Route::get('gestiones', [GestionController::class, 'index']);
        Route::post('gestiones', [GestionController::class, 'store']);
        Route::get('gestiones/{gestion}', [GestionController::class, 'show']);
        Route::put('gestiones/{gestion}', [GestionController::class, 'update']);
        Route::delete('gestiones/{gestion}', [GestionController::class, 'destroy']);

        // Académico: materias
        Route::get('materias', [MateriaController::class, 'index']);
        Route::post('materias', [MateriaController::class, 'store']);
        Route::get('materias/{materia}', [MateriaController::class, 'show']);
        Route::put('materias/{materia}', [MateriaController::class, 'update']);
        Route::delete('materias/{materia}', [MateriaController::class, 'destroy']);

        // Académico: aulas
        Route::get('aulas', [AulaController::class, 'index']);
        Route::post('aulas', [AulaController::class, 'store']);
        Route::get('aulas/{aula}', [AulaController::class, 'show']);
        Route::put('aulas/{aula}', [AulaController::class, 'update']);
        Route::delete('aulas/{aula}', [AulaController::class, 'destroy']);

        // Materias asignadas al aula (con profesor)
        Route::get('aulas/{aula}/materias', [AulaMateriaController::class, 'index']);
        Route::post('aulas/{aula}/materias', [AulaMateriaController::class, 'store']);
        Route::delete('aulas/{aula}/materias/{aulaMateria}', [AulaMateriaController::class, 'destroy']);

        // Inscripciones
        Route::get('aulas/{aula}/inscripciones', [InscripcionController::class, 'index']);
        Route::post('aulas/{aula}/inscripciones', [InscripcionController::class, 'store']);
        Route::post('inscripciones/{inscripcion}/retirar', [InscripcionController::class, 'retirar']);

        // Horario del aula
        Route::get('aulas/{aula}/horario', [HorarioController::class, 'index']);
        Route::post('aulas/{aula}/horario', [HorarioController::class, 'store']);
        Route::put('horarios/{horario}', [HorarioController::class, 'update']);
        Route::delete('horarios/{horario}', [HorarioController::class, 'destroy']);

        // Actividades por materia-aula
        Route::get('materias-aulas/{aulaMateria}/actividades', [ActividadController::class, 'index']);
        Route::post('materias-aulas/{aulaMateria}/actividades', [ActividadController::class, 'store']);
        Route::get('actividades/{actividad}', [ActividadController::class, 'show']);
        Route::put('actividades/{actividad}', [ActividadController::class, 'update']);
        Route::delete('actividades/{actividad}', [ActividadController::class, 'destroy']);
        // Actividades del profesor autenticado (pendientes por fecha límite)
        Route::get('profesor/actividades', [ActividadController::class, 'misActividades']);

        // Notas
        Route::get('actividades/{actividad}/planilla', [NotaController::class, 'planilla']);
        Route::post('actividades/{actividad}/notas', [NotaController::class, 'store']);
        Route::post('actividades/{actividad}/planilla', [NotaController::class, 'guardarPlanilla']);
        Route::put('notas/{nota}', [NotaController::class, 'modificar']);
        Route::get('notas/{nota}/historial', [NotaController::class, 'historial']);

        // Asistencias
        Route::get('profesor/materias-aulas', [AsistenciaController::class, 'misAsignaciones']);
        Route::get('materias-aulas/{aulaMateria}/asistencias/planilla', [AsistenciaController::class, 'planilla']);
        Route::post('materias-aulas/{aulaMateria}/asistencias/toma', [AsistenciaController::class, 'tomar']);
        Route::get('materias-aulas/{aulaMateria}/asistencias', [AsistenciaController::class, 'index']);
        Route::put('asistencias/{asistencia}', [AsistenciaController::class, 'modificar']);
        Route::get('asistencias/{asistencia}/historial', [AsistenciaController::class, 'historial']);

        // Licencias
        Route::get('licencias', [LicenciaController::class, 'index']);
        Route::post('licencias', [LicenciaController::class, 'store']);
        Route::post('licencias/{licencia}/aprobar', [LicenciaController::class, 'aprobar']);
        Route::post('licencias/{licencia}/rechazar', [LicenciaController::class, 'rechazar']);
        Route::delete('licencias/{licencia}', [LicenciaController::class, 'destroy']);

        // Citaciones y comunicados
        Route::get('citaciones', [CitacionController::class, 'index']);
        Route::post('citaciones', [CitacionController::class, 'store']);
        Route::get('citaciones/destinatarios', [CitacionController::class, 'destinatarios']);
        Route::get('citaciones/tipos', [CitacionController::class, 'tipos']);
        Route::get('citaciones/{citacion}', [CitacionController::class, 'show']);
        Route::post('citaciones/{citacion}/marcar-vista', [CitacionController::class, 'marcarVista']);
        Route::delete('citaciones/{citacion}', [CitacionController::class, 'destroy']);

        // Dashboard
        Route::get('dashboard/resumen', [DashboardController::class, 'resumen']);

        // Portal de familia y estudiante
        Route::prefix('portal')->group(function () {
            Route::get('mi-perfil', [PortalController::class, 'miPerfil']);
            Route::get('hijo/{estudiante}', [PortalController::class, 'hijo']);
        });

        // Notificaciones en el sistema
        Route::prefix('notificaciones')->group(function () {
            Route::get('/', [NotificacionController::class, 'index']);
            Route::post('marcar-todas-leidas', [NotificacionController::class, 'marcarTodasLeidas']);
            Route::post('{notificacion}/marcar-leida', [NotificacionController::class, 'marcarLeida']);
        });
    });
});
