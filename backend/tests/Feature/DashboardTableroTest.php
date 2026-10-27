<?php

namespace Tests\Feature;

use App\Enums\EstadoAsistencia;
use App\Enums\EstadoInscripcion;
use App\Enums\EstadoLicencia;
use App\Enums\EstadoUnidad;
use App\Enums\EstadoUsuario;
use App\Enums\Rol;
use App\Enums\Sexo;
use App\Enums\TipoCitacion;
use App\Enums\Turno;
use App\Models\Actividad;
use App\Models\Asistencia;
use App\Models\Aula;
use App\Models\AulaMateria;
use App\Models\Citacion;
use App\Models\Estudiante;
use App\Models\Gestion;
use App\Models\Horario;
use App\Models\Inscripcion;
use App\Models\Licencia;
use App\Models\Macrodistrito;
use App\Models\Materia;
use App\Models\Municipio;
use App\Models\Nota;
use App\Models\UnidadEducativa;
use App\Models\User;
use App\Services\DashboardService;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTableroTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::limpiar();
        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // Escenarios
    // ------------------------------------------------------------------

    private function superadmin(): User
    {
        return User::create([
            'unidad_educativa_id' => null,
            'rol' => Rol::SUPERADMIN,
            'ci' => '10000001',
            'usuario' => '10000001',
            'nombres' => 'Super',
            'apellidos' => 'Admin',
            'password' => 'secret123',
            'debe_cambiar_password' => false,
            'estado' => EstadoUsuario::ACTIVO,
        ]);
    }

    private function usuario(UnidadEducativa $unidad, Rol $rol, string $ci): User
    {
        return User::create([
            'unidad_educativa_id' => $unidad->id,
            'rol' => $rol,
            'ci' => $ci,
            'usuario' => $ci,
            'nombres' => 'Test',
            'apellidos' => $ci,
            'password' => 'secret123',
            'debe_cambiar_password' => false,
            'estado' => EstadoUsuario::ACTIVO,
            'sexo' => Sexo::FEMENINO,
        ]);
    }

    private function estudiante(UnidadEducativa $unidad, string $ci, Sexo $sexo = Sexo::FEMENINO): Estudiante
    {
        $user = User::create([
            'unidad_educativa_id' => $unidad->id,
            'rol' => Rol::ESTUDIANTE,
            'ci' => $ci,
            'usuario' => $ci,
            'nombres' => 'Alumna',
            'apellidos' => $ci,
            'password' => 'secret123',
            'debe_cambiar_password' => false,
            'estado' => EstadoUsuario::ACTIVO,
            'sexo' => $sexo,
        ]);

        return Estudiante::create([
            'user_id' => $user->id,
            'unidad_educativa_id' => $unidad->id,
            'fecha_nacimiento' => '2012-05-05',
            'estado' => EstadoUsuario::ACTIVO,
        ]);
    }

    /**
     * Unidad completa: usuarios, gestión, aula, materia-aula, estudiante inscrito.
     *
     * @return array<string, mixed>
     */
    private function escenario(string $codigo, ?int $macrodistritoId = null): array
    {
        $unidad = UnidadEducativa::create([
            'nombre' => "Unidad {$codigo}",
            'codigo' => "UE-{$codigo}",
            'estado' => EstadoUnidad::ACTIVA,
            'macrodistrito_id' => $macrodistritoId,
        ]);

        $director = $this->usuario($unidad, Rol::DIRECTOR, "70{$codigo}0001");
        $administrativo = $this->usuario($unidad, Rol::ADMINISTRATIVO, "70{$codigo}0002");
        $profesor = $this->usuario($unidad, Rol::PROFESOR, "70{$codigo}0003");
        $padre = $this->usuario($unidad, Rol::PADRE, "70{$codigo}0005");
        $estudiante = $this->estudiante($unidad, "80{$codigo}0001");
        $estudiante->padres()->attach($padre->id, ['parentesco' => 'padre']);

        $gestion = Gestion::create([
            'unidad_educativa_id' => $unidad->id,
            'anio' => now()->year,
            'es_actual' => true,
        ]);

        $aula = Aula::create([
            'unidad_educativa_id' => $unidad->id,
            'gestion_id' => $gestion->id,
            'grado' => '1ro',
            'paralelo' => 'A',
            'turno' => Turno::MANANA,
            'nivel' => 'primaria',
        ]);

        $materia = Materia::create([
            'unidad_educativa_id' => $unidad->id,
            'nombre' => 'Matemáticas',
        ]);

        $aulaMateria = AulaMateria::create([
            'aula_id' => $aula->id,
            'materia_id' => $materia->id,
            'profesor_id' => $profesor->id,
        ]);

        Inscripcion::create([
            'estudiante_id' => $estudiante->id,
            'aula_id' => $aula->id,
            'gestion_id' => $gestion->id,
            'estado' => EstadoInscripcion::INSCRITO,
            'fecha_inscripcion' => now()->toDateString(),
        ]);

        Asistencia::create([
            'aula_materia_id' => $aulaMateria->id,
            'estudiante_id' => $estudiante->id,
            'fecha' => now()->toDateString(),
            'estado' => EstadoAsistencia::PRESENTE,
            'registrado_por' => $profesor->id,
        ]);

        return [
            'unidad' => $unidad,
            'director' => $director,
            'administrativo' => $administrativo,
            'profesor' => $profesor,
            'padre' => $padre,
            'estudiante' => $estudiante,
            'gestion' => $gestion,
            'aula' => $aula,
            'materia' => $materia,
            'aulaMateria' => $aulaMateria,
        ];
    }

    private function macrodistritos(): array
    {
        $municipio = Municipio::create(['nombre' => 'La Paz']);

        $md1 = Macrodistrito::create([
            'municipio_id' => $municipio->id,
            'codigo' => 'MD1',
            'nombre' => 'Cotahuma',
            'tipo' => 'urbano',
        ]);

        $md8 = Macrodistrito::create([
            'municipio_id' => $municipio->id,
            'codigo' => 'MD8',
            'nombre' => 'Zongo',
            'tipo' => 'rural',
        ]);

        return [$md1, $md8];
    }

    // ------------------------------------------------------------------
    // Pruebas
    // ------------------------------------------------------------------

    public function test_superadmin_recibe_vision_municipal_con_filtros(): void
    {
        [$md1, $md8] = $this->macrodistritos();
        $this->escenario('A', $md1->id);
        $this->escenario('B', $md8->id);
        $superadmin = $this->superadmin();

        $respuesta = $this->actingAs($superadmin, 'sanctum')->getJson('/api/v1/dashboard')->assertOk();

        $this->assertSame('superadmin', $respuesta->json('data.rol'));
        $this->assertSame(2, $respuesta->json('data.datos.totales.unidades'));
        $this->assertSame(2, $respuesta->json('data.datos.totales.estudiantes'));
        $this->assertSame(2, $respuesta->json('data.datos.totales.docentes'));
        $this->assertCount(2, $respuesta->json('data.datos.matricula_por_macrodistrito'));
        $this->assertNotEmpty($respuesta->json('data.datos.estudiantes_por_sexo'));
        $this->assertNotEmpty($respuesta->json('data.datos.matricula_por_nivel'));
        $this->assertArrayHasKey('porcentaje', $respuesta->json('data.datos.asistencia_hoy'));
        $this->assertIsArray($respuesta->json('data.datos.ultimas_unidades'));

        // Filtro por macrodistrito: todo el tablero se actualiza.
        $filtrado = $this->actingAs($superadmin, 'sanctum')
            ->getJson("/api/v1/dashboard?macrodistrito_id={$md1->id}")
            ->assertOk();

        $this->assertSame(1, $filtrado->json('data.datos.totales.unidades'));
        $this->assertSame(1, $filtrado->json('data.datos.totales.estudiantes'));
        $this->assertCount(1, $filtrado->json('data.datos.matricula_por_macrodistrito'));
        $this->assertSame('MD1', $filtrado->json('data.datos.matricula_por_macrodistrito.0.codigo'));
        $this->assertCount(1, $filtrado->json('data.datos.ultimas_unidades'));
    }

    public function test_director_recibe_indicadores_y_umbral_configurable(): void
    {
        $e = $this->escenario('D');

        Nota::create([
            'actividad_id' => Actividad::create([
                'aula_materia_id' => $e['aulaMateria']->id,
                'titulo' => 'Examen',
                'fecha_cumplimiento' => now()->toDateString(),
                'fecha_limite_notas' => now()->addDay()->toDateString(),
                'puntaje_maximo' => 100,
            ])->id,
            'estudiante_id' => $e['estudiante']->id,
            'nota' => 60,
            'registrado_por' => $e['profesor']->id,
        ]);

        Licencia::create([
            'estudiante_id' => $e['estudiante']->id,
            'padre_id' => $e['padre']->id,
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => now()->addDays(2)->toDateString(),
            'motivo' => 'Cita médica',
            'estado' => EstadoLicencia::PENDIENTE,
        ]);

        $respuesta = $this->actingAs($e['director'], 'sanctum')->getJson('/api/v1/dashboard')->assertOk();

        $this->assertSame('director', $respuesta->json('data.rol'));
        $this->assertSame(1, $respuesta->json('data.datos.totales.estudiantes'));
        $this->assertSame(51.0, (float) $respuesta->json('data.datos.umbral_notas'));
        $this->assertSame(0, $respuesta->json('data.datos.totales.notas_bajas'));
        $this->assertSame(1, $respuesta->json('data.datos.licencias_pendientes.total'));
        $this->assertCount(30, $respuesta->json('data.datos.tendencia'));
        $this->assertArrayHasKey('riesgo', $respuesta->json('data.datos'));
        $this->assertArrayHasKey('citaciones_recientes', $respuesta->json('data.datos'));
        $this->assertArrayHasKey('profesores_ausentes', $respuesta->json('data.datos'));

        // Umbral configurable: con 90, la nota 60 queda bajo el umbral.
        $conUmbral = $this->actingAs($e['director'], 'sanctum')
            ->getJson('/api/v1/dashboard?umbral=90')
            ->assertOk();

        $this->assertSame(90.0, (float) $conUmbral->json('data.datos.umbral_notas'));
        $this->assertSame(1, $conUmbral->json('data.datos.totales.notas_bajas'));
        $this->assertSame(1, $conUmbral->json('data.datos.riesgo.total'));
        $this->assertSame(60.0, (float) $conUmbral->json('data.datos.riesgo.items.0.promedio'));
    }

    public function test_administrativo_recibe_pendientes_operativos(): void
    {
        $e = $this->escenario('E');

        // Aula sin materias/profesor.
        Aula::create([
            'unidad_educativa_id' => $e['unidad']->id,
            'gestion_id' => $e['gestion']->id,
            'grado' => '2do',
            'paralelo' => 'B',
            'turno' => Turno::MANANA,
            'nivel' => 'primaria',
        ]);

        // Actividad que vence esta semana.
        Actividad::create([
            'aula_materia_id' => $e['aulaMateria']->id,
            'titulo' => 'Tarea',
            'fecha_cumplimiento' => now()->addDays(2)->toDateString(),
            'puntaje_maximo' => 50,
        ]);

        // Estudiante sin inscripción en la gestión actual.
        $this->estudiante($e['unidad'], '80E0002', Sexo::MASCULINO);

        // Citación enviada sin leer.
        Citacion::create([
            'unidad_educativa_id' => $e['unidad']->id,
            'emisor_id' => $e['director']->id,
            'destinatario_id' => $e['padre']->id,
            'estudiante_id' => $e['estudiante']->id,
            'tipo' => TipoCitacion::PADRE,
            'asunto' => 'Reunión',
            'mensaje' => 'Mensaje',
            'fecha_hora' => now()->addDay(),
        ]);

        $respuesta = $this->actingAs($e['administrativo'], 'sanctum')->getJson('/api/v1/dashboard')->assertOk();

        $this->assertSame('administrativo', $respuesta->json('data.rol'));
        $this->assertSame(now()->year, $respuesta->json('data.datos.gestion_actual'));
        $this->assertSame(1, $respuesta->json('data.datos.aulas_sin_profesor.total'));
        $this->assertSame(1, $respuesta->json('data.datos.materias_sin_horario.total'));
        $this->assertSame(1, $respuesta->json('data.datos.actividades_semana.total'));
        $this->assertSame(1, $respuesta->json('data.datos.estudiantes_sin_inscribir.total'));
        $this->assertSame(1, $respuesta->json('data.datos.citaciones.enviadas'));
        $this->assertSame(1, $respuesta->json('data.datos.citaciones.sin_leer'));
    }

    public function test_profesor_recibe_su_jornada(): void
    {
        $e = $this->escenario('F');

        // Horario de hoy (si no es domingo).
        $dia = match (now()->dayOfWeek) {
            1 => 'lunes', 2 => 'martes', 3 => 'miercoles',
            4 => 'jueves', 5 => 'viernes', 6 => 'sabado', default => null,
        };

        if ($dia) {
            Horario::create([
                'aula_materia_id' => $e['aulaMateria']->id,
                'dia' => $dia,
                'hora_inicio' => '08:00',
                'hora_fin' => '08:45',
            ]);
        }

        // Segundo aula-materia del profesor, sin asistencia tomada hoy:
        // la del escenario ya tiene asistencia, esta queda pendiente.
        $aula2 = Aula::create([
            'unidad_educativa_id' => $e['unidad']->id,
            'gestion_id' => $e['gestion']->id,
            'grado' => '2do',
            'paralelo' => 'B',
            'turno' => Turno::MANANA,
            'nivel' => 'primaria',
        ]);

        $materia2 = Materia::create([
            'unidad_educativa_id' => $e['unidad']->id,
            'nombre' => 'Lenguaje',
        ]);

        AulaMateria::create([
            'aula_id' => $aula2->id,
            'materia_id' => $materia2->id,
            'profesor_id' => $e['profesor']->id,
        ]);

        $respuesta = $this->actingAs($e['profesor'], 'sanctum')->getJson('/api/v1/dashboard')->assertOk();

        $this->assertSame('profesor', $respuesta->json('data.rol'));
        $this->assertSame($dia, $respuesta->json('data.datos.dia'));
        $this->assertSame($dia ? 1 : 0, $respuesta->json('data.datos.horario_hoy.total'));
        $this->assertSame(1, $respuesta->json('data.datos.asistencias_pendientes.total'));
        $this->assertSame(2, $respuesta->json('data.datos.mis_aulas.total'));
        $this->assertSame(2, $respuesta->json('data.datos.mis_materias.total'));
        $this->assertArrayHasKey('actividades', $respuesta->json('data.datos'));
    }

    public function test_padre_recibe_tarjetas_por_hijo(): void
    {
        $e = $this->escenario('G');

        // Nota, actividad pendiente y licencia en curso del hijo.
        $actividad = Actividad::create([
            'aula_materia_id' => $e['aulaMateria']->id,
            'titulo' => 'Tarea',
            'fecha_cumplimiento' => now()->toDateString(),
            'puntaje_maximo' => 50,
        ]);

        Nota::create([
            'actividad_id' => $actividad->id,
            'estudiante_id' => $e['estudiante']->id,
            'nota' => 80,
            'registrado_por' => $e['profesor']->id,
        ]);

        Actividad::create([
            'aula_materia_id' => $e['aulaMateria']->id,
            'titulo' => 'Proyecto',
            'fecha_cumplimiento' => now()->addDays(5)->toDateString(),
            'puntaje_maximo' => 50,
        ]);

        Citacion::create([
            'unidad_educativa_id' => $e['unidad']->id,
            'emisor_id' => $e['director']->id,
            'destinatario_id' => $e['padre']->id,
            'estudiante_id' => $e['estudiante']->id,
            'tipo' => TipoCitacion::PADRE,
            'asunto' => 'Reunión',
            'mensaje' => 'Mensaje',
            'fecha_hora' => now()->addDay(),
        ]);

        $respuesta = $this->actingAs($e['padre'], 'sanctum')->getJson('/api/v1/dashboard')->assertOk();

        $this->assertSame('padre', $respuesta->json('data.rol'));
        $this->assertSame(1, $respuesta->json('data.datos.totales.hijos'));
        $this->assertSame(1, $respuesta->json('data.datos.totales.citaciones_sin_leer'));

        $hijo = $respuesta->json('data.datos.hijos.0');
        $this->assertSame('1ro A', $hijo['aula']);
        $this->assertArrayHasKey('asistencia_mes', $hijo);
        $this->assertSame(100.0, (float) $hijo['asistencia_mes']['porcentaje']);
        $this->assertCount(1, $hijo['ultimas_notas']);
        $this->assertSame(1, $hijo['actividades_pendientes']);
        $this->assertSame(1, $hijo['citaciones_sin_leer']);
    }

    public function test_estudiante_recibe_su_panel(): void
    {
        $e = $this->escenario('H');

        $actividad = Actividad::create([
            'aula_materia_id' => $e['aulaMateria']->id,
            'titulo' => 'Examen',
            'fecha_cumplimiento' => now()->addDays(3)->toDateString(),
            'puntaje_maximo' => 100,
        ]);

        Nota::create([
            'actividad_id' => $actividad->id,
            'estudiante_id' => $e['estudiante']->id,
            'nota' => 90,
            'registrado_por' => $e['profesor']->id,
        ]);

        $respuesta = $this->actingAs($e['estudiante']->user, 'sanctum')
            ->getJson('/api/v1/dashboard')
            ->assertOk();

        $this->assertSame('estudiante', $respuesta->json('data.rol'));
        $this->assertSame('1ro A', $respuesta->json('data.datos.aula'));
        $this->assertSame(1, $respuesta->json('data.datos.proximas_actividades.total'));
        $this->assertFalse($respuesta->json('data.datos.proximas_actividades.items.0.sin_nota'));
        $this->assertSame(1, $respuesta->json('data.datos.ultimas_notas.total'));
        $this->assertSame(90.0, (float) $respuesta->json('data.datos.ultimas_notas.items.0.nota'));
        $this->assertSame(100.0, (float) $respuesta->json('data.datos.asistencia_mes.porcentaje'));
    }

    public function test_cada_rol_recibe_su_propia_seccion(): void
    {
        $e = $this->escenario('I');

        $this->actingAs($e['director'], 'sanctum')->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.rol', 'director');

        $this->actingAs($e['profesor'], 'sanctum')->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.rol', 'profesor');

        $this->actingAs($e['padre'], 'sanctum')->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.rol', 'padre');

        // El padre no recibe la visión municipal del superadmin.
        $padre = $this->actingAs($e['padre'], 'sanctum')->getJson('/api/v1/dashboard')->assertOk();
        $this->assertArrayNotHasKey('matricula_por_macrodistrito', $padre->json('data.datos'));
    }

    public function test_cache_60_segundos_y_invalidacion(): void
    {
        $e = $this->escenario('J');
        $director = $e['director'];

        $primera = $this->actingAs($director, 'sanctum')->getJson('/api/v1/dashboard')->assertOk();
        $this->assertSame(0, $primera->json('data.datos.licencias_pendientes.total'));

        // Escritura directa en BD: no dispara observadores, por lo que el
        // valor cacheado debe seguir devolviéndose (prueba de que hay caché).
        DB::table('licencias')->insert([
            'estudiante_id' => $e['estudiante']->id,
            'padre_id' => $e['padre']->id,
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => now()->addDay()->toDateString(),
            'motivo' => 'Cita médica',
            'estado' => EstadoLicencia::PENDIENTE->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $segunda = $this->actingAs($director, 'sanctum')->getJson('/api/v1/dashboard')->assertOk();
        $this->assertSame(0, $segunda->json('data.datos.licencias_pendientes.total'));

        // La invalidación por versión fuerza el recálculo.
        DashboardService::invalidar();

        $tercera = $this->actingAs($director, 'sanctum')->getJson('/api/v1/dashboard')->assertOk();
        $this->assertSame(1, $tercera->json('data.datos.licencias_pendientes.total'));
    }

    public function test_el_cache_es_por_unidad_y_usuario(): void
    {
        $a = $this->escenario('K');
        $b = $this->escenario('L');

        $datosA = $this->actingAs($a['director'], 'sanctum')->getJson('/api/v1/dashboard')->assertOk();
        $datosB = $this->actingAs($b['director'], 'sanctum')->getJson('/api/v1/dashboard')->assertOk();

        // Cada usuario ve los datos de su propia unidad (claves distintas).
        $this->assertSame(1, $datosA->json('data.datos.totales.estudiantes'));
        $this->assertSame(1, $datosB->json('data.datos.totales.estudiantes'));
        $this->assertSame('UE-K', $this->unidadDe($a['unidad']));
        $this->assertSame('UE-L', $this->unidadDe($b['unidad']));
    }

    private function unidadDe(UnidadEducativa $unidad): string
    {
        return $unidad->fresh()->codigo;
    }
}
