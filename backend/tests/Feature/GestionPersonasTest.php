<?php

namespace Tests\Feature;

use App\Enums\EstadoUnidad;
use App\Enums\EstadoUsuario;
use App\Enums\Rol;
use App\Models\UnidadEducativa;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GestionPersonasTest extends TestCase
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

    public function test_superadmin_crea_unidad_con_director(): void
    {
        $superadmin = $this->usuario($this->unidad('SA'), Rol::SUPERADMIN, '90000001');

        $respuesta = $this->actingAs($superadmin, 'sanctum')->postJson('/api/v1/unidades', [
            'nombre' => 'Unidad Nueva',
            'codigo' => 'UE-NUEVA',
            'director' => [
                'ci' => '80000001',
                'nombres' => 'Nuevo',
                'apellidos' => 'Director',
            ],
        ]);

        $respuesta->assertCreated();
        $this->assertDatabaseHas('unidades_educativas', ['codigo' => 'UE-NUEVA']);
        $this->assertDatabaseHas('users', [
            'ci' => '80000001',
            'rol' => Rol::DIRECTOR->value,
        ]);

        $director = User::where('ci', '80000001')->first();
        $this->assertTrue($director->debe_cambiar_password);
        $this->assertSame($director->ci, $director->usuario);
    }

    public function test_director_no_puede_crear_unidades(): void
    {
        $unidad = $this->unidad('A');
        $director = $this->usuario($unidad, Rol::DIRECTOR, '70000001');

        $this->actingAs($director, 'sanctum')->postJson('/api/v1/unidades', [
            'nombre' => 'X', 'codigo' => 'X',
            'director' => ['ci' => '1', 'nombres' => 'A', 'apellidos' => 'B'],
        ])->assertForbidden();
    }

    public function test_director_crea_profesor_en_su_unidad(): void
    {
        $unidad = $this->unidad('A');
        $director = $this->usuario($unidad, Rol::DIRECTOR, '70000001');

        $respuesta = $this->actingAs($director, 'sanctum')->postJson('/api/v1/personas', [
            'rol' => Rol::PROFESOR->value,
            'ci' => '60000001',
            'nombres' => 'Profesor',
            'apellidos' => 'Nuevo',
        ]);

        $respuesta->assertCreated();
        $profesor = User::where('ci', '60000001')->first();
        $this->assertSame($unidad->id, $profesor->unidad_educativa_id);
        $this->assertSame(Rol::PROFESOR, $profesor->rol);
    }

    public function test_director_crea_estudiante_con_padre_nuevo(): void
    {
        $unidad = $this->unidad('A');
        $director = $this->usuario($unidad, Rol::DIRECTOR, '70000001');

        $respuesta = $this->actingAs($director, 'sanctum')->postJson('/api/v1/estudiantes', [
            'estudiante' => [
                'ci' => '50000001',
                'nombres' => 'Lucia',
                'apellidos' => 'Perez',
                'fecha_nacimiento' => '2015-03-12',
            ],
            'padre_nuevo' => [
                'ci' => '40000001',
                'nombres' => 'Jorge',
                'apellidos' => 'Perez',
            ],
            'parentesco' => 'padre',
        ]);

        $respuesta->assertCreated();

        $estudiante = User::where('ci', '50000001')->first();
        $padre = User::where('ci', '40000001')->first();

        $this->assertSame(Rol::ESTUDIANTE, $estudiante->rol);
        $this->assertSame(Rol::PADRE, $padre->rol);
        $this->assertTrue($padre->hijos()->whereKey($estudiante->estudiante->id)->exists());
    }

    public function test_padre_puede_tener_varios_hijos(): void
    {
        $unidad = $this->unidad('A');
        $director = $this->usuario($unidad, Rol::DIRECTOR, '70000001');

        $this->actingAs($director, 'sanctum')->postJson('/api/v1/estudiantes', [
            'estudiante' => ['ci' => '50000001', 'nombres' => 'Hijo', 'apellidos' => 'Uno'],
            'padre_nuevo' => ['ci' => '40000001', 'nombres' => 'Padre', 'apellidos' => 'X'],
        ])->assertCreated();

        $padre = User::where('ci', '40000001')->first();

        $this->actingAs($director, 'sanctum')->postJson('/api/v1/estudiantes', [
            'estudiante' => ['ci' => '50000002', 'nombres' => 'Hijo', 'apellidos' => 'Dos'],
            'padre_existente_id' => $padre->id,
        ])->assertCreated();

        $this->assertSame(2, $padre->hijos()->count());
    }

    public function test_dar_de_baja_estudiante_con_motivo_y_fecha(): void
    {
        $unidad = $this->unidad('A');
        $director = $this->usuario($unidad, Rol::DIRECTOR, '70000001');

        $this->actingAs($director, 'sanctum')->postJson('/api/v1/estudiantes', [
            'estudiante' => ['ci' => '50000001', 'nombres' => 'Hijo', 'apellidos' => 'Uno'],
            'padre_nuevo' => ['ci' => '40000001', 'nombres' => 'Padre', 'apellidos' => 'X'],
        ])->assertCreated();

        $estudiante = User::where('ci', '50000001')->first();

        $respuesta = $this->actingAs($director, 'sanctum')->postJson(
            "/api/v1/estudiantes/{$estudiante->estudiante->id}/baja",
            ['motivo' => 'Traslado de colegio', 'fecha' => '2026-10-19']
        );

        $respuesta->assertOk();
        $this->assertSame(EstadoUsuario::BAJA, $estudiante->fresh()->estado);
        $this->assertSame('Traslado de colegio', $estudiante->fresh()->motivo_baja);
        $this->assertSame(EstadoUsuario::BAJA->value, $respuesta->json('data.estado'));
        $this->assertSame('Traslado de colegio', $respuesta->json('data.motivo_baja'));
    }

    public function test_director_no_ve_personas_de_otra_unidad(): void
    {
        $unidadA = $this->unidad('A');
        $unidadB = $this->unidad('B');

        $directorA = $this->usuario($unidadA, Rol::DIRECTOR, '70000001');
        $profesorB = $this->usuario($unidadB, Rol::PROFESOR, '60000002');

        $respuesta = $this->actingAs($directorA, 'sanctum')
            ->getJson('/api/v1/personas');

        $respuesta->assertOk();
        $ids = collect($respuesta->json('data'))->pluck('id');
        $this->assertFalse($ids->contains($profesorB->id));
    }

    public function test_administrativo_crea_estudiante_con_padre(): void
    {
        $unidad = $this->unidad('A');
        $admin = $this->usuario($unidad, Rol::ADMINISTRATIVO, '70000002');

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/estudiantes', [
            'estudiante' => ['ci' => '50000003', 'nombres' => 'Nuevo', 'apellidos' => 'Alumno'],
            'padre_nuevo' => ['ci' => '40000003', 'nombres' => 'Padre', 'apellidos' => 'Nuevo'],
        ])->assertCreated();

        $this->assertDatabaseHas('users', ['ci' => '50000003', 'rol' => Rol::ESTUDIANTE->value]);
        $this->assertDatabaseHas('users', ['ci' => '40000003', 'rol' => Rol::PADRE->value]);
    }

    public function test_administrativo_no_puede_dar_de_baja(): void
    {
        $unidad = $this->unidad('A');
        $admin = $this->usuario($unidad, Rol::ADMINISTRATIVO, '70000002');
        $profesor = $this->usuario($unidad, Rol::PROFESOR, '60000005');

        $this->actingAs($admin, 'sanctum')->postJson(
            "/api/v1/personas/{$profesor->id}/baja",
            ['motivo' => 'X', 'fecha' => '2026-10-19']
        )->assertForbidden();
    }
}
