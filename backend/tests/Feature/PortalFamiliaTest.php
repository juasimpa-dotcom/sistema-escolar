<?php

namespace Tests\Feature;

use App\Enums\EstadoAsistencia;
use App\Enums\EstadoCitacion;
use App\Enums\EstadoInscripcion;
use App\Enums\EstadoLicencia;
use App\Enums\EstadoUnidad;
use App\Enums\EstadoUsuario;
use App\Enums\Rol;
use App\Enums\TipoCitacion;
use App\Enums\Turno;
use App\Models\Actividad;
use App\Models\Asistencia;
use App\Models\Aula;
use App\Models\AulaMateria;
use App\Models\Citacion;
use App\Models\Estudiante;
use App\Models\Gestion;
use App\Models\Inscripcion;
use App\Models\Licencia;
use App\Models\Materia;
use App\Models\Nota;
use App\Models\UnidadEducativa;
use App\Models\User;
use App\Notifications\CitacionRecibida;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalFamiliaTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::limpiar();
        parent::tearDown();
    }

    private function unidad(string $codigo = 'A'): UnidadEducativa
    {
        return UnidadEducativa::create([
            'nombre' => "Unidad {$codigo}",
            'codigo' => $codigo,
            'estado' => EstadoUnidad::ACTIVA,
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
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function escenario(string $codigo = 'A'): array
    {
        $unidad = $this->unidad($codigo);
        $director = $this->usuario($unidad, Rol::DIRECTOR, "70{$codigo}0001");
        $profesor = $this->usuario($unidad, Rol::PROFESOR, "70{$codigo}0003");
        $padre = $this->usuario($unidad, Rol::PADRE, "70{$codigo}0005");

        $estudianteUser = $this->usuario($unidad, Rol::ESTUDIANTE, "80{$codigo}0001");
        $estudiante = Estudiante::create([
            'user_id' => $estudianteUser->id,
            'unidad_educativa_id' => $unidad->id,
            'fecha_nacimiento' => '2010-05-05',
            'estado' => EstadoUsuario::ACTIVO,
        ]);
        $estudiante->padres()->attach($padre->id, ['parentesco' => 'padre']);

        $gestion = Gestion::create([
            'unidad_educativa_id' => $unidad->id,
            'anio' => 2026,
            'es_actual' => true,
        ]);

        $aula = Aula::create([
            'unidad_educativa_id' => $unidad->id,
            'gestion_id' => $gestion->id,
            'grado' => '1ro',
            'paralelo' => 'A',
            'turno' => Turno::MANANA,
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

        $actividad = Actividad::create([
            'aula_materia_id' => $aulaMateria->id,
            'titulo' => 'Prueba 1',
            'fecha_cumplimiento' => now()->toDateString(),
            'puntaje_maximo' => 100,
        ]);

        return compact(
            'unidad', 'director', 'profesor', 'padre', 'estudiante',
            'estudianteUser', 'gestion', 'aula', 'materia', 'aulaMateria', 'actividad'
        );
    }

    public function test_padre_ve_el_portal_completo_de_su_hijo(): void
    {
        $e = $this->escenario();

        Nota::create([
            'actividad_id' => $e['actividad']->id,
            'estudiante_id' => $e['estudiante']->id,
            'nota' => 85,
            'fue_modificada' => false,
            'registrado_por' => $e['profesor']->id,
        ]);

        Asistencia::create([
            'aula_materia_id' => $e['aulaMateria']->id,
            'estudiante_id' => $e['estudiante']->id,
            'fecha' => now()->toDateString(),
            'estado' => EstadoAsistencia::ATRASO,
            'registrado_por' => $e['profesor']->id,
        ]);

        Licencia::create([
            'estudiante_id' => $e['estudiante']->id,
            'padre_id' => $e['padre']->id,
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => now()->addDay()->toDateString(),
            'motivo' => 'Cita médica',
            'estado' => EstadoLicencia::PENDIENTE,
        ]);

        $resp = $this->actingAs($e['padre'], 'sanctum')
            ->getJson("/api/v1/portal/hijo/{$e['estudiante']->id}")
            ->assertOk();

        $resp->assertJsonPath('data.aula.nombre', '1ro A')
            ->assertJsonPath('data.materias.0.materia', 'Matemáticas')
            ->assertJsonPath('data.materias.0.profesor', $e['profesor']->nombreCompleto())
            ->assertJsonPath('data.materias.0.actividades.0.nota', 85)
            ->assertJsonPath('data.asistencia.atrasos', 1)
            ->assertJsonPath('data.licencias.0.estado', 'pendiente');

        $this->assertEmpty($resp->json('data.pendientes'));
    }

    public function test_padre_no_ve_el_portal_de_un_estudiante_ajeno(): void
    {
        $e = $this->escenario();
        $otroPadre = $this->usuario($e['unidad'], Rol::PADRE, '70999888');

        $this->actingAs($otroPadre, 'sanctum')
            ->getJson("/api/v1/portal/hijo/{$e['estudiante']->id}")
            ->assertStatus(403);
    }

    public function test_pendientes_muestra_actividades_sin_nota(): void
    {
        $e = $this->escenario();

        $this->actingAs($e['padre'], 'sanctum')
            ->getJson("/api/v1/portal/hijo/{$e['estudiante']->id}")
            ->assertOk()
            ->assertJsonPath('data.pendientes.0.titulo', 'Prueba 1')
            ->assertJsonPath('data.materias.0.actividades.0.registrada', false);
    }

    public function test_estudiante_ve_su_propio_perfil(): void
    {
        $e = $this->escenario();

        $this->actingAs($e['estudianteUser'], 'sanctum')
            ->getJson('/api/v1/portal/mi-perfil')
            ->assertOk()
            ->assertJsonPath('data.aula.nombre', '1ro A')
            ->assertJsonPath('data.estudiante.nombre_completo', $e['estudianteUser']->nombreCompleto());
    }

    public function test_padre_recibe_notificacion_al_llegar_citacion(): void
    {
        $e = $this->escenario();

        $this->actingAs($e['director'], 'sanctum')->postJson('/api/v1/citaciones', [
            'destinatario_id' => $e['padre']->id,
            'estudiante_id' => $e['estudiante']->id,
            'tipo' => TipoCitacion::PADRE->value,
            'asunto' => 'Reunión por rendimiento',
            'mensaje' => 'Se solicita su presencia.',
            'fecha_hora' => now()->addDay()->format('Y-m-d H:i:s'),
        ])->assertCreated();

        $this->assertSame(1, $e['padre']->fresh()->unreadNotifications()->count());

        $this->actingAs($e['padre'], 'sanctum')
            ->getJson('/api/v1/notificaciones')
            ->assertOk()
            ->assertJsonPath('data.0.tipo', 'citacion')
            ->assertJsonPath('no_leidas', 1);
    }

    public function test_padre_recibe_notificacion_al_resolverse_licencia(): void
    {
        $e = $this->escenario();

        $licencia = Licencia::create([
            'estudiante_id' => $e['estudiante']->id,
            'padre_id' => $e['padre']->id,
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => now()->addDay()->toDateString(),
            'motivo' => 'Trámite',
            'estado' => EstadoLicencia::PENDIENTE,
        ]);

        $this->actingAs($e['director'], 'sanctum')
            ->postJson("/api/v1/licencias/{$licencia->id}/aprobar")
            ->assertOk();

        $this->assertSame(1, $e['padre']->fresh()->unreadNotifications()->count());

        $this->actingAs($e['padre'], 'sanctum')
            ->getJson('/api/v1/notificaciones')
            ->assertOk()
            ->assertJsonPath('data.0.tipo', 'licencia')
            ->assertJsonPath('data.0.datos.estado', 'aprobada');
    }

    public function test_marcar_notificacion_como_leida(): void
    {
        $e = $this->escenario();

        $citacion = Citacion::create([
            'unidad_educativa_id' => $e['unidad']->id,
            'emisor_id' => $e['director']->id,
            'destinatario_id' => $e['padre']->id,
            'estudiante_id' => $e['estudiante']->id,
            'tipo' => TipoCitacion::PADRE,
            'asunto' => 'X',
            'mensaje' => 'Y',
            'fecha_hora' => now()->addDay(),
            'estado' => EstadoCitacion::ENVIADA,
        ]);
        $e['padre']->notify(new CitacionRecibida($citacion));

        $id = $e['padre']->fresh()->unreadNotifications()->first()->id;

        $this->actingAs($e['padre'], 'sanctum')
            ->postJson("/api/v1/notificaciones/{$id}/marcar-leida")
            ->assertOk();

        $this->assertSame(0, $e['padre']->fresh()->unreadNotifications()->count());
    }

    public function test_marcar_todas_las_notificaciones_como_leidas(): void
    {
        $e = $this->escenario();

        foreach (range(1, 3) as $i) {
            $citacion = Citacion::create([
                'unidad_educativa_id' => $e['unidad']->id,
                'emisor_id' => $e['director']->id,
                'destinatario_id' => $e['padre']->id,
                'estudiante_id' => $e['estudiante']->id,
                'tipo' => TipoCitacion::PADRE,
                'asunto' => "Asunto {$i}",
                'mensaje' => 'Y',
                'fecha_hora' => now()->addDays($i),
                'estado' => EstadoCitacion::ENVIADA,
            ]);
            $e['padre']->notify(new CitacionRecibida($citacion));
        }

        $this->assertSame(3, $e['padre']->fresh()->unreadNotifications()->count());

        $this->actingAs($e['padre'], 'sanctum')
            ->postJson('/api/v1/notificaciones/marcar-todas-leidas')
            ->assertOk();

        $this->assertSame(0, $e['padre']->fresh()->unreadNotifications()->count());
    }

    public function test_director_no_ve_notificaciones_de_citaciones_de_otro(): void
    {
        $e = $this->escenario();

        $this->actingAs($e['director'], 'sanctum')
            ->getJson('/api/v1/notificaciones')
            ->assertOk()
            ->assertJsonPath('no_leidas', 0);
    }
}
