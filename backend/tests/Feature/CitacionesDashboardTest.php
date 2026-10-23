<?php

namespace Tests\Feature;

use App\Enums\EstadoAsistencia;
use App\Enums\EstadoCitacion;
use App\Enums\EstadoInscripcion;
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
use App\Models\Materia;
use App\Models\Nota;
use App\Models\UnidadEducativa;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CitacionesDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::limpiar();
        parent::tearDown();
    }

    private function unidad(string $codigo): UnidadEducativa
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

    private function estudiante(UnidadEducativa $unidad, string $ci): Estudiante
    {
        $user = $this->usuario($unidad, Rol::ESTUDIANTE, $ci);

        return Estudiante::create([
            'user_id' => $user->id,
            'unidad_educativa_id' => $unidad->id,
            'fecha_nacimiento' => '2010-05-05',
            'estado' => EstadoUsuario::ACTIVO,
        ]);
    }

    /**
     * @return array{unidad: UnidadEducativa, director: User, administrativo: User, profesor: User, padre: User, estudiante: Estudiante, aula: Aula, aulaMateria: AulaMateria, gestion: Gestion}
     */
    private function escenario(string $codigo = 'A'): array
    {
        $unidad = $this->unidad($codigo);
        $director = $this->usuario($unidad, Rol::DIRECTOR, "70{$codigo}0001");
        $administrativo = $this->usuario($unidad, Rol::ADMINISTRATIVO, "70{$codigo}0002");
        $profesor = $this->usuario($unidad, Rol::PROFESOR, "70{$codigo}0003");
        $padre = $this->usuario($unidad, Rol::PADRE, "70{$codigo}0005");
        $estudiante = $this->estudiante($unidad, "80{$codigo}0001");
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
            'nombre' => 'MatemÃ¡ticas',
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

        return compact('unidad', 'director', 'administrativo', 'profesor', 'padre', 'estudiante', 'aula', 'aulaMateria', 'gestion');
    }

    public function test_director_envia_citacion_a_padre(): void
    {
        $e = $this->escenario();

        $resp = $this->actingAs($e['director'], 'sanctum')->postJson('/api/v1/citaciones', [
            'destinatario_id' => $e['padre']->id,
            'estudiante_id' => $e['estudiante']->id,
            'tipo' => TipoCitacion::PADRE->value,
            'asunto' => 'ReuniÃ³n por rendimiento',
            'mensaje' => 'Se solicita su presencia para conversar sobre el rendimiento del estudiante.',
            'fecha_hora' => now()->addDay()->format('Y-m-d H:i:s'),
            'lugar' => 'DirecciÃ³n',
        ]);

        $resp->assertCreated()
            ->assertJsonPath('data.tipo', 'padre')
            ->assertJsonPath('data.estado', 'enviada');

        $this->assertDatabaseHas('citaciones', [
            'emisor_id' => $e['director']->id,
            'destinatario_id' => $e['padre']->id,
            'estado' => 'enviada',
        ]);
    }

    public function test_administrativo_envia_comunicado_a_profesor(): void
    {
        $e = $this->escenario();

        $this->actingAs($e['administrativo'], 'sanctum')->postJson('/api/v1/citaciones', [
            'destinatario_id' => $e['profesor']->id,
            'tipo' => TipoCitacion::PROFESOR_INFORMATIVA->value,
            'asunto' => 'ReuniÃ³n informativa',
            'mensaje' => 'ReuniÃ³n informativa de coordinaciÃ³n.',
            'fecha_hora' => now()->addDays(2)->format('Y-m-d H:i:s'),
        ])->assertCreated()->assertJsonPath('data.tipo', 'profesor_informativa');
    }

    public function test_profesor_no_puede_emitir_citacion(): void
    {
        $e = $this->escenario();

        $this->actingAs($e['profesor'], 'sanctum')->postJson('/api/v1/citaciones', [
            'destinatario_id' => $e['padre']->id,
            'estudiante_id' => $e['estudiante']->id,
            'tipo' => TipoCitacion::PADRE->value,
            'asunto' => 'X',
            'mensaje' => 'Y',
            'fecha_hora' => now()->addDay()->format('Y-m-d H:i:s'),
        ])->assertStatus(403);
    }

    public function test_citacion_a_padre_exige_tutor_del_estudiante(): void
    {
        $e = $this->escenario();
        $ajeno = $this->usuario($e['unidad'], Rol::PADRE, '70999999');

        $this->actingAs($e['director'], 'sanctum')->postJson('/api/v1/citaciones', [
            'destinatario_id' => $ajeno->id,
            'estudiante_id' => $e['estudiante']->id,
            'tipo' => TipoCitacion::PADRE->value,
            'asunto' => 'X',
            'mensaje' => 'Y',
            'fecha_hora' => now()->addDay()->format('Y-m-d H:i:s'),
        ])->assertStatus(422)->assertJsonValidationErrors('destinatario_id');
    }

    public function test_padre_marca_citacion_como_vista(): void
    {
        $e = $this->escenario();

        $citacion = Citacion::create([
            'unidad_educativa_id' => $e['unidad']->id,
            'emisor_id' => $e['director']->id,
            'destinatario_id' => $e['padre']->id,
            'estudiante_id' => $e['estudiante']->id,
            'tipo' => TipoCitacion::PADRE,
            'asunto' => 'ReuniÃ³n',
            'mensaje' => 'Mensaje',
            'fecha_hora' => now()->addDay(),
            'estado' => EstadoCitacion::ENVIADA,
        ]);

        $this->actingAs($e['padre'], 'sanctum')
            ->postJson("/api/v1/citaciones/{$citacion->id}/marcar-vista")
            ->assertOk()
            ->assertJsonPath('data.estado', 'vista');

        $this->assertDatabaseHas('citaciones', ['id' => $citacion->id, 'estado' => 'vista']);
    }

    public function test_padre_solo_ve_sus_citaciones_recibidas(): void
    {
        $e = $this->escenario();
        $otroPadre = $this->usuario($e['unidad'], Rol::PADRE, '70777777');

        Citacion::create([
            'unidad_educativa_id' => $e['unidad']->id,
            'emisor_id' => $e['director']->id,
            'destinatario_id' => $e['padre']->id,
            'estudiante_id' => $e['estudiante']->id,
            'tipo' => TipoCitacion::PADRE,
            'asunto' => 'MÃ­a',
            'mensaje' => 'Mensaje',
            'fecha_hora' => now()->addDay(),
        ]);

        Citacion::create([
            'unidad_educativa_id' => $e['unidad']->id,
            'emisor_id' => $e['director']->id,
            'destinatario_id' => $otroPadre->id,
            'estudiante_id' => $e['estudiante']->id,
            'tipo' => TipoCitacion::PADRE,
            'asunto' => 'Ajena',
            'mensaje' => 'Mensaje',
            'fecha_hora' => now()->addDay(),
        ]);

        $resp = $this->actingAs($e['padre'], 'sanctum')
            ->getJson('/api/v1/citaciones')
            ->assertOk();

        $this->assertCount(1, $resp->json('data'));
        $this->assertSame('MÃ­a', $resp->json('data.0.asunto'));
    }

    public function test_director_anula_citacion_enviada(): void
    {
        $e = $this->escenario();

        $citacion = Citacion::create([
            'unidad_educativa_id' => $e['unidad']->id,
            'emisor_id' => $e['director']->id,
            'destinatario_id' => $e['padre']->id,
            'estudiante_id' => $e['estudiante']->id,
            'tipo' => TipoCitacion::PADRE,
            'asunto' => 'ReuniÃ³n',
            'mensaje' => 'Mensaje',
            'fecha_hora' => now()->addDay(),
        ]);

        $this->actingAs($e['director'], 'sanctum')
            ->deleteJson("/api/v1/citaciones/{$citacion->id}")
            ->assertOk();

        $this->assertDatabaseMissing('citaciones', ['id' => $citacion->id]);
    }

    public function test_dashboard_resumen_calcula_indicadores(): void
    {
        $e = $this->escenario();
        $hoy = now()->toDateString();

        // Asistencias: 1 presente, 1 falta, 1 licencia.
        Asistencia::create([
            'aula_materia_id' => $e['aulaMateria']->id,
            'estudiante_id' => $e['estudiante']->id,
            'fecha' => $hoy,
            'estado' => EstadoAsistencia::PRESENTE,
            'registrado_por' => $e['profesor']->id,
        ]);

        $est2 = $this->estudiante($e['unidad'], '80A0002');
        Inscripcion::create([
            'estudiante_id' => $est2->id,
            'aula_id' => $e['aula']->id,
            'gestion_id' => $e['gestion']->id,
            'estado' => EstadoInscripcion::INSCRITO,
            'fecha_inscripcion' => $hoy,
        ]);

        Asistencia::create([
            'aula_materia_id' => $e['aulaMateria']->id,
            'estudiante_id' => $est2->id,
            'fecha' => $hoy,
            'estado' => EstadoAsistencia::FALTA,
            'registrado_por' => $e['profesor']->id,
        ]);

        $est3 = $this->estudiante($e['unidad'], '80A0003');
        Inscripcion::create([
            'estudiante_id' => $est3->id,
            'aula_id' => $e['aula']->id,
            'gestion_id' => $e['gestion']->id,
            'estado' => EstadoInscripcion::INSCRITO,
            'fecha_inscripcion' => $hoy,
        ]);

        Asistencia::create([
            'aula_materia_id' => $e['aulaMateria']->id,
            'estudiante_id' => $est3->id,
            'fecha' => $hoy,
            'estado' => EstadoAsistencia::LICENCIA,
            'registrado_por' => $e['profesor']->id,
        ]);

        // Nota baja para el estudiante 1.
        $actividad = Actividad::create([
            'aula_materia_id' => $e['aulaMateria']->id,
            'titulo' => 'Examen',
            'fecha_cumplimiento' => $hoy,
            'fecha_limite_notas' => $hoy,
            'puntaje_maximo' => 100,
        ]);

        Nota::create([
            'actividad_id' => $actividad->id,
            'estudiante_id' => $e['estudiante']->id,
            'nota' => 30,
            'registrado_por' => $e['profesor']->id,
        ]);

        $resp = $this->actingAs($e['director'], 'sanctum')
            ->getJson("/api/v1/dashboard/resumen?fecha={$hoy}&umbral=51")
            ->assertOk();

        $this->assertSame(3, $resp->json('data.total_estudiantes'));
        $this->assertSame(1, $resp->json('data.con_faltas'));
        $this->assertSame(1, $resp->json('data.con_licencia'));
        $this->assertSame(1, $resp->json('data.con_notas_bajas'));
        $this->assertSame(33.3, $resp->json('data.porcentaje_presentes'));
    }

    public function test_dashboard_filtra_por_aula(): void
    {
        $e = $this->escenario();
        $hoy = now()->toDateString();

        // Aula 2 sin estudiantes.
        $aula2 = Aula::create([
            'unidad_educativa_id' => $e['unidad']->id,
            'gestion_id' => $e['gestion']->id,
            'grado' => '2do',
            'paralelo' => 'B',
            'turno' => Turno::MANANA,
        ]);

        $resp = $this->actingAs($e['director'], 'sanctum')
            ->getJson("/api/v1/dashboard/resumen?aula_id={$aula2->id}&fecha={$hoy}")
            ->assertOk();

        $this->assertSame(0, $resp->json('data.total_estudiantes'));
    }

    public function test_administrativo_ve_dashboard_y_padre_no(): void
    {
        $e = $this->escenario();
        $hoy = now()->toDateString();

        $this->actingAs($e['administrativo'], 'sanctum')
            ->getJson("/api/v1/dashboard/resumen?fecha={$hoy}")
            ->assertOk();

        $this->actingAs($e['padre'], 'sanctum')
            ->getJson("/api/v1/dashboard/resumen?fecha={$hoy}")
            ->assertStatus(403);
    }

    public function test_dashboard_resumen_se_invalida_al_mutar_asistencia(): void
    {
        $e = $this->escenario();
        $hoy = now()->toDateString();

        // Primer cálculo: sin faltas (calienta la caché).
        $inicial = $this->actingAs($e['director'], 'sanctum')
            ->getJson("/api/v1/dashboard/resumen?fecha={$hoy}")
            ->assertOk();
        $this->assertSame(0, $inicial->json('data.con_faltas'));

        // Mutación: registrar una falta.
        Asistencia::create([
            'aula_materia_id' => $e['aulaMateria']->id,
            'estudiante_id' => $e['estudiante']->id,
            'fecha' => $hoy,
            'estado' => EstadoAsistencia::FALTA,
            'registrado_por' => $e['profesor']->id,
        ]);

        // Segundo cálculo: la caché debe haberse invalidado vía observer.
        $actualizado = $this->actingAs($e['director'], 'sanctum')
            ->getJson("/api/v1/dashboard/resumen?fecha={$hoy}")
            ->assertOk();
        $this->assertSame(1, $actualizado->json('data.con_faltas'));
    }

    public function test_director_no_ve_citaciones_de_otra_unidad(): void
    {
        $a = $this->escenario('A');
        $b = $this->escenario('B');

        Citacion::create([
            'unidad_educativa_id' => $b['unidad']->id,
            'emisor_id' => $b['director']->id,
            'destinatario_id' => $b['padre']->id,
            'estudiante_id' => $b['estudiante']->id,
            'tipo' => TipoCitacion::PADRE,
            'asunto' => 'Otra unidad',
            'mensaje' => 'Mensaje',
            'fecha_hora' => now()->addDay(),
        ]);

        $resp = $this->actingAs($a['director'], 'sanctum')
            ->getJson('/api/v1/citaciones')
            ->assertOk();

        $this->assertCount(0, $resp->json('data'));
    }
}
