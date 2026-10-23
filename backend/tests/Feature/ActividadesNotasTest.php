<?php

namespace Tests\Feature;

use App\Enums\EstadoInscripcion;
use App\Enums\EstadoUnidad;
use App\Enums\EstadoUsuario;
use App\Enums\Rol;
use App\Enums\Turno;
use App\Models\Actividad;
use App\Models\Aula;
use App\Models\AulaMateria;
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

class ActividadesNotasTest extends TestCase
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
     * @return array{0: AulaMateria, 1: Estudiante, 2: User}
     */
    private function escenario(): array
    {
        $unidad = $this->unidad('A');
        $profesor = $this->usuario($unidad, Rol::PROFESOR, '70000001');
        $estudiante = $this->estudiante($unidad, '80000001');

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

        return [$aulaMateria, $estudiante, $profesor];
    }

    public function test_administrativo_crea_actividad_en_materia_aula(): void
    {
        [$aulaMateria] = $this->escenario();
        $admin = $this->usuario($aulaMateria->aula->unidadEducativa, Rol::ADMINISTRATIVO, '70000002');

        $resp = $this->actingAs($admin, 'sanctum')->postJson(
            "/api/v1/materias-aulas/{$aulaMateria->id}/actividades",
            [
                'titulo' => 'Primer parcial',
                'descripcion' => 'Temas 1 al 4',
                'fecha_cumplimiento' => '2026-06-10',
                'fecha_limite_notas' => '2026-06-17',
                'puntaje_maximo' => 100,
            ]
        );

        $resp->assertCreated()
            ->assertJsonPath('data.titulo', 'Primer parcial')
            ->assertJsonPath('data.puntaje_maximo', 100);

        $this->assertDatabaseHas('actividades', ['titulo' => 'Primer parcial', 'aula_materia_id' => $aulaMateria->id]);
    }

    public function test_fecha_limite_anterior_a_cumplimiento_es_rechazada(): void
    {
        [$aulaMateria] = $this->escenario();
        $admin = $this->usuario($aulaMateria->aula->unidadEducativa, Rol::ADMINISTRATIVO, '70000002');

        $this->actingAs($admin, 'sanctum')->postJson(
            "/api/v1/materias-aulas/{$aulaMateria->id}/actividades",
            [
                'titulo' => 'Mal fechada',
                'fecha_cumplimiento' => '2026-06-17',
                'fecha_limite_notas' => '2026-06-10',
                'puntaje_maximo' => 100,
            ]
        )->assertStatus(422)->assertJsonValidationErrors('fecha_limite_notas');
    }

    public function test_profesor_ve_solo_sus_actividades_ordenadas(): void
    {
        [$aulaMateria, , $profesor] = $this->escenario();
        $unidad = $aulaMateria->aula->unidadEducativa;

        $otroProfesor = $this->usuario($unidad, Rol::PROFESOR, '70000009');
        $otraMateria = Materia::create(['unidad_educativa_id' => $unidad->id, 'nombre' => 'Lenguaje']);
        $otroAulaMateria = AulaMateria::create([
            'aula_id' => $aulaMateria->aula_id,
            'materia_id' => $otraMateria->id,
            'profesor_id' => $otroProfesor->id,
        ]);

        Actividad::create([
            'aula_materia_id' => $aulaMateria->id,
            'titulo' => 'Tarea tarde',
            'fecha_cumplimiento' => '2026-07-01',
            'fecha_limite_notas' => '2026-07-20',
            'puntaje_maximo' => 100,
        ]);
        Actividad::create([
            'aula_materia_id' => $aulaMateria->id,
            'titulo' => 'Tarea pronto',
            'fecha_cumplimiento' => '2026-06-01',
            'fecha_limite_notas' => '2026-06-05',
            'puntaje_maximo' => 100,
        ]);
        Actividad::create([
            'aula_materia_id' => $otroAulaMateria->id,
            'titulo' => 'Ajena',
            'fecha_cumplimiento' => '2026-06-01',
            'fecha_limite_notas' => '2026-06-02',
            'puntaje_maximo' => 100,
        ]);

        $resp = $this->actingAs($profesor, 'sanctum')->getJson('/api/v1/profesor/actividades');

        $resp->assertOk();
        $titulos = collect($resp->json('data'))->pluck('titulo');
        $this->assertSame(['Tarea pronto', 'Tarea tarde'], $titulos->all());
    }

    public function test_profesor_registra_planilla_de_notas(): void
    {
        [$aulaMateria, $estudiante, $profesor] = $this->escenario();

        $actividad = Actividad::create([
            'aula_materia_id' => $aulaMateria->id,
            'titulo' => 'Primer parcial',
            'fecha_cumplimiento' => '2026-06-10',
            'fecha_limite_notas' => '2026-06-17',
            'puntaje_maximo' => 100,
        ]);

        $this->actingAs($profesor, 'sanctum')->postJson(
            "/api/v1/actividades/{$actividad->id}/planilla",
            ['notas' => [['estudiante_id' => $estudiante->id, 'nota' => 85]]]
        )->assertOk();

        $this->assertDatabaseHas('notas', [
            'actividad_id' => $actividad->id,
            'estudiante_id' => $estudiante->id,
            'nota' => 85,
        ]);

        $planilla = $this->actingAs($profesor, 'sanctum')
            ->getJson("/api/v1/actividades/{$actividad->id}/planilla");

        $planilla->assertOk()
            ->assertJsonPath('data.filas.0.estudiante_id', $estudiante->id)
            ->assertJsonPath('data.filas.0.nota', 85);
    }

    public function test_nota_mayor_al_puntaje_maximo_es_rechazada(): void
    {
        [$aulaMateria, $estudiante, $profesor] = $this->escenario();

        $actividad = Actividad::create([
            'aula_materia_id' => $aulaMateria->id,
            'titulo' => 'Primer parcial',
            'fecha_cumplimiento' => '2026-06-10',
            'puntaje_maximo' => 100,
        ]);

        $this->actingAs($profesor, 'sanctum')->postJson(
            "/api/v1/actividades/{$actividad->id}/notas",
            ['estudiante_id' => $estudiante->id, 'nota' => 150]
        )->assertStatus(422)->assertJsonValidationErrors('nota');
    }

    public function test_nota_de_estudiante_no_inscrito_es_rechazada(): void
    {
        [$aulaMateria, , $profesor] = $this->escenario();
        $ajeno = $this->estudiante($aulaMateria->aula->unidadEducativa, '80000099');

        $actividad = Actividad::create([
            'aula_materia_id' => $aulaMateria->id,
            'titulo' => 'Primer parcial',
            'fecha_cumplimiento' => '2026-06-10',
            'puntaje_maximo' => 100,
        ]);

        $this->actingAs($profesor, 'sanctum')->postJson(
            "/api/v1/actividades/{$actividad->id}/notas",
            ['estudiante_id' => $ajeno->id, 'nota' => 80]
        )->assertStatus(422)->assertJsonValidationErrors('estudiante_id');
    }

    public function test_profesor_modifica_nota_una_sola_vez_con_observacion(): void
    {
        [$aulaMateria, $estudiante, $profesor] = $this->escenario();

        $actividad = Actividad::create([
            'aula_materia_id' => $aulaMateria->id,
            'titulo' => 'Primer parcial',
            'fecha_cumplimiento' => '2026-06-10',
            'puntaje_maximo' => 100,
        ]);

        $nota = Nota::create([
            'actividad_id' => $actividad->id,
            'estudiante_id' => $estudiante->id,
            'nota' => 70,
            'registrado_por' => $profesor->id,
        ]);

        $this->actingAs($profesor, 'sanctum')->putJson("/api/v1/notas/{$nota->id}", [
            'nota' => 80,
            'observacion' => 'Se corrigió por error de suma.',
        ])->assertOk()
            ->assertJsonPath('data.nota', 80)
            ->assertJsonPath('data.fue_modificada', true);

        $this->assertDatabaseHas('nota_historial', [
            'nota_id' => $nota->id,
            'nota_anterior' => 70,
            'nota_nueva' => 80,
        ]);

        // Segunda modificación bloqueada.
        $this->actingAs($profesor, 'sanctum')->putJson("/api/v1/notas/{$nota->id}", [
            'nota' => 90,
            'observacion' => 'Otra vez.',
        ])->assertStatus(422)->assertJsonValidationErrors('nota');
    }

    public function test_modificar_nota_sin_observacion_es_rechazado(): void
    {
        [$aulaMateria, $estudiante, $profesor] = $this->escenario();

        $actividad = Actividad::create([
            'aula_materia_id' => $aulaMateria->id,
            'titulo' => 'Primer parcial',
            'fecha_cumplimiento' => '2026-06-10',
            'puntaje_maximo' => 100,
        ]);

        $nota = Nota::create([
            'actividad_id' => $actividad->id,
            'estudiante_id' => $estudiante->id,
            'nota' => 70,
            'registrado_por' => $profesor->id,
        ]);

        $this->actingAs($profesor, 'sanctum')->putJson("/api/v1/notas/{$nota->id}", [
            'nota' => 80,
        ])->assertStatus(422)->assertJsonValidationErrors('observacion');
    }

    public function test_profesor_de_otra_materia_no_puede_registrar_nota(): void
    {
        [$aulaMateria, $estudiante] = $this->escenario();
        $intruso = $this->usuario($aulaMateria->aula->unidadEducativa, Rol::PROFESOR, '70000050');

        $actividad = Actividad::create([
            'aula_materia_id' => $aulaMateria->id,
            'titulo' => 'Primer parcial',
            'fecha_cumplimiento' => '2026-06-10',
            'puntaje_maximo' => 100,
        ]);

        $this->actingAs($intruso, 'sanctum')->postJson(
            "/api/v1/actividades/{$actividad->id}/notas",
            ['estudiante_id' => $estudiante->id, 'nota' => 80]
        )->assertStatus(403);
    }

    public function test_no_se_elimina_actividad_con_notas(): void
    {
        [$aulaMateria, $estudiante, $profesor] = $this->escenario();
        $admin = $this->usuario($aulaMateria->aula->unidadEducativa, Rol::ADMINISTRATIVO, '70000002');

        $actividad = Actividad::create([
            'aula_materia_id' => $aulaMateria->id,
            'titulo' => 'Primer parcial',
            'fecha_cumplimiento' => '2026-06-10',
            'puntaje_maximo' => 100,
        ]);

        Nota::create([
            'actividad_id' => $actividad->id,
            'estudiante_id' => $estudiante->id,
            'nota' => 70,
            'registrado_por' => $profesor->id,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/actividades/{$actividad->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('actividades', ['id' => $actividad->id, 'deleted_at' => null]);
    }
}
