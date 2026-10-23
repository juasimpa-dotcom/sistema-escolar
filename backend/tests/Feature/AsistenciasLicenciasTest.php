<?php

namespace Tests\Feature;

use App\Enums\EstadoAsistencia;
use App\Enums\EstadoInscripcion;
use App\Enums\EstadoLicencia;
use App\Enums\EstadoUnidad;
use App\Enums\EstadoUsuario;
use App\Enums\Rol;
use App\Enums\Turno;
use App\Models\Asistencia;
use App\Models\Aula;
use App\Models\AulaMateria;
use App\Models\Estudiante;
use App\Models\Gestion;
use App\Models\Inscripcion;
use App\Models\Licencia;
use App\Models\Materia;
use App\Models\UnidadEducativa;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsistenciasLicenciasTest extends TestCase
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
     * @return array{aulaMateria: AulaMateria, estudiante: Estudiante, profesor: User, padre: User, unidad: UnidadEducativa}
     */
    private function escenario(): array
    {
        $unidad = $this->unidad('A');
        $profesor = $this->usuario($unidad, Rol::PROFESOR, '70000001');
        $estudiante = $this->estudiante($unidad, '80000001');
        $padre = $this->usuario($unidad, Rol::PADRE, '70000005');
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

        return compact('aulaMateria', 'estudiante', 'profesor', 'padre', 'unidad');
    }

    public function test_padre_solicita_licencia_para_su_hijo(): void
    {
        $e = $this->escenario();
        $hoy = now();
        $inicio = $hoy->copy()->addDay()->toDateString();
        $fin = $hoy->copy()->addDays(3)->toDateString();

        $resp = $this->actingAs($e['padre'], 'sanctum')->postJson('/api/v1/licencias', [
            'estudiante_id' => $e['estudiante']->id,
            'fecha_inicio' => $inicio,
            'fecha_fin' => $fin,
            'motivo' => 'Cita médica',
        ]);

        $resp->assertCreated()
            ->assertJsonPath('data.estado', 'pendiente');

        $this->assertDatabaseHas('licencias', [
            'estudiante_id' => $e['estudiante']->id,
            'padre_id' => $e['padre']->id,
            'estado' => 'pendiente',
        ]);
    }

    public function test_padre_no_puede_solicitar_para_estudiante_ajeno(): void
    {
        $e = $this->escenario();
        $ajeno = $this->estudiante($e['unidad'], '80000099');

        $this->actingAs($e['padre'], 'sanctum')->postJson('/api/v1/licencias', [
            'estudiante_id' => $ajeno->id,
            'fecha_inicio' => now()->addDay()->toDateString(),
            'fecha_fin' => now()->addDays(2)->toDateString(),
            'motivo' => 'Intento',
        ])->assertStatus(403);
    }

    public function test_director_aprueba_y_rechaza_licencia(): void
    {
        $e = $this->escenario();
        $director = $this->usuario($e['unidad'], Rol::DIRECTOR, '70000002');

        $licencia = Licencia::create([
            'estudiante_id' => $e['estudiante']->id,
            'padre_id' => $e['padre']->id,
            'fecha_inicio' => now()->addDay()->toDateString(),
            'fecha_fin' => now()->addDays(2)->toDateString(),
            'motivo' => 'Cita médica',
            'estado' => EstadoLicencia::PENDIENTE,
        ]);

        $this->actingAs($director, 'sanctum')
            ->postJson("/api/v1/licencias/{$licencia->id}/aprobar")
            ->assertOk()
            ->assertJsonPath('data.estado', 'aprobada');

        $this->assertDatabaseHas('licencias', ['id' => $licencia->id, 'estado' => 'aprobada']);
    }

    public function test_director_rechaza_licencia_exigiendo_motivo(): void
    {
        $e = $this->escenario();
        $director = $this->usuario($e['unidad'], Rol::DIRECTOR, '70000002');

        $licencia = Licencia::create([
            'estudiante_id' => $e['estudiante']->id,
            'padre_id' => $e['padre']->id,
            'fecha_inicio' => now()->addDay()->toDateString(),
            'fecha_fin' => now()->addDays(2)->toDateString(),
            'motivo' => 'Cita médica',
            'estado' => EstadoLicencia::PENDIENTE,
        ]);

        $this->actingAs($director, 'sanctum')
            ->postJson("/api/v1/licencias/{$licencia->id}/rechazar", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('motivo_rechazo');

        $this->actingAs($director, 'sanctum')
            ->postJson("/api/v1/licencias/{$licencia->id}/rechazar", ['motivo_rechazo' => 'No presentó comprobante'])
            ->assertOk()
            ->assertJsonPath('data.estado', 'rechazada');
    }

    public function test_profesor_no_puede_aprobar_licencia(): void
    {
        $e = $this->escenario();

        $licencia = Licencia::create([
            'estudiante_id' => $e['estudiante']->id,
            'padre_id' => $e['padre']->id,
            'fecha_inicio' => now()->addDay()->toDateString(),
            'fecha_fin' => now()->addDays(2)->toDateString(),
            'motivo' => 'Cita médica',
            'estado' => EstadoLicencia::PENDIENTE,
        ]);

        $this->actingAs($e['profesor'], 'sanctum')
            ->postJson("/api/v1/licencias/{$licencia->id}/aprobar")
            ->assertStatus(403);
    }

    public function test_asistencia_se_fuerza_a_licencia_con_licencia_aprobada(): void
    {
        $e = $this->escenario();
        $fecha = now()->toDateString();

        Licencia::create([
            'estudiante_id' => $e['estudiante']->id,
            'padre_id' => $e['padre']->id,
            'fecha_inicio' => $fecha,
            'fecha_fin' => $fecha,
            'motivo' => 'Cita médica',
            'estado' => EstadoLicencia::APROBADA,
        ]);

        $this->actingAs($e['profesor'], 'sanctum')->postJson(
            "/api/v1/materias-aulas/{$e['aulaMateria']->id}/asistencias/toma",
            [
                'fecha' => $fecha,
                'registros' => [
                    ['estudiante_id' => $e['estudiante']->id, 'estado' => 'falta'],
                ],
            ]
        )->assertOk();

        $this->assertDatabaseHas('asistencias', [
            'aula_materia_id' => $e['aulaMateria']->id,
            'estudiante_id' => $e['estudiante']->id,
            'estado' => 'licencia',
        ]);
    }

    public function test_profesor_ve_licencia_en_planilla_y_no_puede_cambiarla(): void
    {
        $e = $this->escenario();
        $fecha = now()->toDateString();

        Licencia::create([
            'estudiante_id' => $e['estudiante']->id,
            'padre_id' => $e['padre']->id,
            'fecha_inicio' => $fecha,
            'fecha_fin' => $fecha,
            'motivo' => 'Cita médica',
            'estado' => EstadoLicencia::APROBADA,
        ]);

        $this->actingAs($e['profesor'], 'sanctum')
            ->getJson("/api/v1/materias-aulas/{$e['aulaMateria']->id}/asistencias/planilla?fecha={$fecha}")
            ->assertOk()
            ->assertJsonPath('data.filas.0.tiene_licencia', true)
            ->assertJsonPath('data.filas.0.estado', 'licencia');

        $asistencia = Asistencia::create([
            'aula_materia_id' => $e['aulaMateria']->id,
            'estudiante_id' => $e['estudiante']->id,
            'fecha' => $fecha,
            'estado' => EstadoAsistencia::LICENCIA,
            'registrado_por' => $e['profesor']->id,
        ]);

        $this->actingAs($e['profesor'], 'sanctum')
            ->putJson("/api/v1/asistencias/{$asistencia->id}", ['estado' => 'presente'])
            ->assertStatus(422);
    }

    public function test_profesor_registra_asistencia_normal(): void
    {
        $e = $this->escenario();
        $fecha = now()->subDay()->toDateString();

        $this->actingAs($e['profesor'], 'sanctum')->postJson(
            "/api/v1/materias-aulas/{$e['aulaMateria']->id}/asistencias/toma",
            [
                'fecha' => $fecha,
                'registros' => [
                    ['estudiante_id' => $e['estudiante']->id, 'estado' => 'atraso', 'observacion' => 'Llegó 10 min tarde'],
                ],
            ]
        )->assertOk();

        $this->assertDatabaseHas('asistencias', [
            'estudiante_id' => $e['estudiante']->id,
            'estado' => 'atraso',
        ]);
    }

    public function test_director_modifica_asistencia_con_auditoria(): void
    {
        $e = $this->escenario();
        $director = $this->usuario($e['unidad'], Rol::DIRECTOR, '70000002');

        $asistencia = Asistencia::create([
            'aula_materia_id' => $e['aulaMateria']->id,
            'estudiante_id' => $e['estudiante']->id,
            'fecha' => now()->toDateString(),
            'estado' => EstadoAsistencia::FALTA,
            'registrado_por' => $e['profesor']->id,
        ]);

        $this->actingAs($director, 'sanctum')
            ->putJson("/api/v1/asistencias/{$asistencia->id}", [
                'estado' => 'presente',
                'observacion' => 'Corrección autorizada por dirección',
            ])
            ->assertOk()
            ->assertJsonPath('data.estado', 'presente')
            ->assertJsonPath('data.historial.0.estado_anterior', 'falta')
            ->assertJsonPath('data.historial.0.estado_nuevo', 'presente');

        $this->assertDatabaseHas('asistencia_historial', [
            'asistencia_id' => $asistencia->id,
            'estado_anterior' => 'falta',
            'estado_nuevo' => 'presente',
            'usuario_id' => $director->id,
        ]);
    }

    public function test_no_se_registra_asistencia_de_fecha_futura(): void
    {
        $e = $this->escenario();

        $this->actingAs($e['profesor'], 'sanctum')->postJson(
            "/api/v1/materias-aulas/{$e['aulaMateria']->id}/asistencias/toma",
            [
                'fecha' => now()->addDay()->toDateString(),
                'registros' => [
                    ['estudiante_id' => $e['estudiante']->id, 'estado' => 'presente'],
                ],
            ]
        )->assertStatus(422)->assertJsonValidationErrors('fecha');
    }

    public function test_profesor_de_otra_materia_no_toma_asistencia(): void
    {
        $e = $this->escenario();
        $intruso = $this->usuario($e['unidad'], Rol::PROFESOR, '70000050');

        $this->actingAs($intruso, 'sanctum')->postJson(
            "/api/v1/materias-aulas/{$e['aulaMateria']->id}/asistencias/toma",
            [
                'fecha' => now()->toDateString(),
                'registros' => [
                    ['estudiante_id' => $e['estudiante']->id, 'estado' => 'presente'],
                ],
            ]
        )->assertStatus(403);
    }
}
