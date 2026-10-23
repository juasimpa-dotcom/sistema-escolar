<?php

namespace Tests\Feature;

use App\Enums\EstadoInscripcion;
use App\Enums\EstadoUnidad;
use App\Enums\EstadoUsuario;
use App\Enums\Rol;
use App\Enums\Turno;
use App\Models\Aula;
use App\Models\AulaMateria;
use App\Models\Estudiante;
use App\Models\Gestion;
use App\Models\Materia;
use App\Models\UnidadEducativa;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GestionAcademicaTest extends TestCase
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

    private function gestion(UnidadEducativa $unidad, int $anio = 2026): Gestion
    {
        return Gestion::create([
            'unidad_educativa_id' => $unidad->id,
            'anio' => $anio,
            'es_actual' => true,
        ]);
    }

    private function aula(UnidadEducativa $unidad, Gestion $gestion, string $grado = '1ro'): Aula
    {
        return Aula::create([
            'unidad_educativa_id' => $unidad->id,
            'gestion_id' => $gestion->id,
            'grado' => $grado,
            'paralelo' => 'A',
            'turno' => Turno::MANANA,
        ]);
    }

    public function test_administrativo_crea_gestion_aula_y_materia(): void
    {
        $unidad = $this->unidad('A');
        $admin = $this->usuario($unidad, Rol::ADMINISTRATIVO, '70000002');

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/gestiones', [
            'anio' => 2026,
            'es_actual' => true,
        ])->assertCreated();

        $gestion = Gestion::where('anio', 2026)->first();
        $this->assertSame($unidad->id, $gestion->unidad_educativa_id);

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/materias', [
            'nombre' => 'Matemáticas',
        ])->assertCreated();

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/aulas', [
            'gestion_id' => $gestion->id,
            'grado' => '1ro',
            'paralelo' => 'A',
            'turno' => Turno::MANANA->value,
        ])->assertCreated();

        $this->assertDatabaseHas('aulas', ['grado' => '1ro', 'unidad_educativa_id' => $unidad->id]);
    }

    public function test_profesor_no_puede_crear_gestion(): void
    {
        $unidad = $this->unidad('A');
        $profesor = $this->usuario($unidad, Rol::PROFESOR, '60000001');

        $this->actingAs($profesor, 'sanctum')->postJson('/api/v1/gestiones', [
            'anio' => 2026,
        ])->assertForbidden();
    }

    public function test_asignar_materia_a_aula_con_profesor(): void
    {
        $unidad = $this->unidad('A');
        $admin = $this->usuario($unidad, Rol::ADMINISTRATIVO, '70000002');
        $profesor = $this->usuario($unidad, Rol::PROFESOR, '60000001');
        $gestion = $this->gestion($unidad);
        $aula = $this->aula($unidad, $gestion);
        $materia = Materia::create(['unidad_educativa_id' => $unidad->id, 'nombre' => 'Lenguaje']);

        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/aulas/{$aula->id}/materias", [
            'materia_id' => $materia->id,
            'profesor_id' => $profesor->id,
        ])->assertCreated();

        $this->assertDatabaseHas('aula_materia', [
            'aula_id' => $aula->id,
            'materia_id' => $materia->id,
            'profesor_id' => $profesor->id,
        ]);
    }

    public function test_no_asigna_profesor_de_otra_unidad(): void
    {
        $unidadA = $this->unidad('A');
        $unidadB = $this->unidad('B');
        $adminA = $this->usuario($unidadA, Rol::ADMINISTRATIVO, '70000002');
        $profesorB = $this->usuario($unidadB, Rol::PROFESOR, '60000009');
        $gestion = $this->gestion($unidadA);
        $aula = $this->aula($unidadA, $gestion);
        $materia = Materia::create(['unidad_educativa_id' => $unidadA->id, 'nombre' => 'Lenguaje']);

        $this->actingAs($adminA, 'sanctum')->postJson("/api/v1/aulas/{$aula->id}/materias", [
            'materia_id' => $materia->id,
            'profesor_id' => $profesorB->id,
        ])->assertStatus(422);
    }

    public function test_horario_rechaza_choque_de_profesor(): void
    {
        $unidad = $this->unidad('A');
        $admin = $this->usuario($unidad, Rol::ADMINISTRATIVO, '70000002');
        $profesor = $this->usuario($unidad, Rol::PROFESOR, '60000001');
        $gestion = $this->gestion($unidad);
        $aulaUno = $this->aula($unidad, $gestion, '1ro');
        $aulaDos = $this->aula($unidad, $gestion, '2do');

        $materia = Materia::create(['unidad_educativa_id' => $unidad->id, 'nombre' => 'Física']);

        $amUno = AulaMateria::create(['aula_id' => $aulaUno->id, 'materia_id' => $materia->id, 'profesor_id' => $profesor->id]);
        $amDos = AulaMateria::create(['aula_id' => $aulaDos->id, 'materia_id' => $materia->id, 'profesor_id' => $profesor->id]);

        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/aulas/{$aulaUno->id}/horario", [
            'aula_materia_id' => $amUno->id,
            'dia' => 'lunes',
            'hora_inicio' => '08:00',
            'hora_fin' => '09:00',
        ])->assertCreated();

        // Mismo profesor, otra aula, horario solapado
        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/aulas/{$aulaDos->id}/horario", [
            'aula_materia_id' => $amDos->id,
            'dia' => 'lunes',
            'hora_inicio' => '08:30',
            'hora_fin' => '09:30',
        ])->assertStatus(422);

        $this->assertDatabaseCount('horarios', 1);
    }

    public function test_horario_permite_bloques_contiguos_sin_solape(): void
    {
        $unidad = $this->unidad('A');
        $admin = $this->usuario($unidad, Rol::ADMINISTRATIVO, '70000002');
        $profesor = $this->usuario($unidad, Rol::PROFESOR, '60000001');
        $gestion = $this->gestion($unidad);
        $aula = $this->aula($unidad, $gestion);
        $materia = Materia::create(['unidad_educativa_id' => $unidad->id, 'nombre' => 'Historia']);
        $am = AulaMateria::create(['aula_id' => $aula->id, 'materia_id' => $materia->id, 'profesor_id' => $profesor->id]);

        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/aulas/{$aula->id}/horario", [
            'aula_materia_id' => $am->id, 'dia' => 'martes', 'hora_inicio' => '08:00', 'hora_fin' => '09:00',
        ])->assertCreated();

        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/aulas/{$aula->id}/horario", [
            'aula_materia_id' => $am->id, 'dia' => 'martes', 'hora_inicio' => '09:00', 'hora_fin' => '10:00',
        ])->assertCreated();

        $this->assertDatabaseCount('horarios', 2);
    }

    public function test_inscribir_estudiante_y_retirarlo(): void
    {
        $unidad = $this->unidad('A');
        $admin = $this->usuario($unidad, Rol::ADMINISTRATIVO, '70000002');
        $gestion = $this->gestion($unidad);
        $aula = $this->aula($unidad, $gestion);

        $userEst = $this->usuario($unidad, Rol::ESTUDIANTE, '50000001');
        $estudiante = Estudiante::create([
            'user_id' => $userEst->id,
            'unidad_educativa_id' => $unidad->id,
            'fecha_nacimiento' => '2015-03-12',
        ]);

        $respuesta = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/aulas/{$aula->id}/inscripciones", [
            'estudiante_id' => $estudiante->id,
        ])->assertCreated();

        $inscripcionId = $respuesta->json('data.id');
        $this->assertDatabaseHas('inscripciones', [
            'estudiante_id' => $estudiante->id,
            'aula_id' => $aula->id,
            'estado' => EstadoInscripcion::INSCRITO->value,
        ]);

        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/inscripciones/{$inscripcionId}/retirar", [
            'motivo' => 'Retiro voluntario',
            'fecha' => '2026-06-01',
        ])->assertOk();

        $this->assertDatabaseHas('inscripciones', [
            'id' => $inscripcionId,
            'estado' => EstadoInscripcion::RETIRADO->value,
        ]);
    }

    public function test_no_inscribe_estudiante_dos_veces_en_la_gestion(): void
    {
        $unidad = $this->unidad('A');
        $admin = $this->usuario($unidad, Rol::ADMINISTRATIVO, '70000002');
        $gestion = $this->gestion($unidad);
        $aula = $this->aula($unidad, $gestion);

        $userEst = $this->usuario($unidad, Rol::ESTUDIANTE, '50000001');
        $estudiante = Estudiante::create([
            'user_id' => $userEst->id,
            'unidad_educativa_id' => $unidad->id,
            'fecha_nacimiento' => '2015-03-12',
        ]);

        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/aulas/{$aula->id}/inscripciones", [
            'estudiante_id' => $estudiante->id,
        ])->assertCreated();

        // No se puede inscribir dos veces en la misma gestión
        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/aulas/{$aula->id}/inscripciones", [
            'estudiante_id' => $estudiante->id,
        ])->assertStatus(422);
    }

    public function test_admin_no_ve_aulas_de_otra_unidad(): void
    {
        $unidadA = $this->unidad('A');
        $unidadB = $this->unidad('B');
        $adminA = $this->usuario($unidadA, Rol::ADMINISTRATIVO, '70000002');

        $gestionB = $this->gestion($unidadB);
        $aulaB = $this->aula($unidadB, $gestionB);

        $respuesta = $this->actingAs($adminA, 'sanctum')->getJson('/api/v1/aulas');

        $respuesta->assertOk();
        $ids = collect($respuesta->json('data'))->pluck('id');
        $this->assertFalse($ids->contains($aulaB->id));
    }
}
