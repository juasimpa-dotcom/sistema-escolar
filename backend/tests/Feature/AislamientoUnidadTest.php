<?php

namespace Tests\Feature;

use App\Enums\EstadoUnidad;
use App\Enums\EstadoUsuario;
use App\Enums\Rol;
use App\Models\Aula;
use App\Models\Estudiante;
use App\Models\Gestion;
use App\Models\UnidadEducativa;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AislamientoUnidadTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::limpiar();
        parent::tearDown();
    }

    private function crearUnidad(string $codigo): UnidadEducativa
    {
        return UnidadEducativa::create([
            'nombre' => "Unidad {$codigo}",
            'codigo' => $codigo,
            'estado' => EstadoUnidad::ACTIVA,
        ]);
    }

    private function crearDirector(UnidadEducativa $unidad, string $ci): User
    {
        return User::create([
            'unidad_educativa_id' => $unidad->id,
            'rol' => Rol::DIRECTOR,
            'ci' => $ci,
            'usuario' => $ci,
            'nombres' => 'Director',
            'apellidos' => $codigo = $ci,
            'password' => bcrypt('secret123'),
            'debe_cambiar_password' => false,
            'estado' => EstadoUsuario::ACTIVO,
        ]);
    }

    public function test_director_de_unidad_a_no_ve_datos_de_unidad_b(): void
    {
        $unidadA = $this->crearUnidad('UE-A');
        $unidadB = $this->crearUnidad('UE-B');

        $directorA = $this->crearDirector($unidadA, '111');

        Aula::create(['gestion_id' => Gestion::create([
            'unidad_educativa_id' => $unidadA->id, 'anio' => 2026, 'es_actual' => true,
        ])->id, 'unidad_educativa_id' => $unidadA->id, 'grado' => '1ro', 'paralelo' => 'A']);

        Aula::create(['gestion_id' => Gestion::create([
            'unidad_educativa_id' => $unidadB->id, 'anio' => 2026, 'es_actual' => true,
        ])->id, 'unidad_educativa_id' => $unidadB->id, 'grado' => '2do', 'paralelo' => 'B']);

        TenantContext::set($unidadA->id, false);

        $this->assertSame(1, Aula::count(), 'El director A solo debe ver las aulas de su unidad.');
        $this->assertSame($unidadA->id, Aula::first()->unidad_educativa_id);

        $aulasViaApi = $this->actingAs($directorA, 'sanctum')->getJson('/api/v1/auth/me');
        $aulasViaApi->assertOk();

        TenantContext::set($unidadB->id, false);
        $this->assertSame(1, Aula::count());
        $this->assertSame($unidadB->id, Aula::first()->unidad_educativa_id);
    }

    public function test_superadmin_ve_todas_las_unidades(): void
    {
        $unidadA = $this->crearUnidad('UE-A');
        $unidadB = $this->crearUnidad('UE-B');

        Gestion::create(['unidad_educativa_id' => $unidadA->id, 'anio' => 2026, 'es_actual' => true]);
        Gestion::create(['unidad_educativa_id' => $unidadB->id, 'anio' => 2026, 'es_actual' => true]);

        Aula::create(['gestion_id' => Gestion::where('unidad_educativa_id', $unidadA->id)->first()->id, 'unidad_educativa_id' => $unidadA->id, 'grado' => '1ro', 'paralelo' => 'A']);
        Aula::create(['gestion_id' => Gestion::where('unidad_educativa_id', $unidadB->id)->first()->id, 'unidad_educativa_id' => $unidadB->id, 'grado' => '2do', 'paralelo' => 'B']);

        TenantContext::set(null, true);

        $this->assertSame(2, Aula::count(), 'El superadmin debe ver aulas de ambas unidades.');
        $this->assertSame(2, UnidadEducativa::count());
    }

    public function test_estudiante_solo_ve_su_unidad(): void
    {
        $unidadA = $this->crearUnidad('UE-A');
        $unidadB = $this->crearUnidad('UE-B');

        Estudiante::create(['user_id' => User::create([
            'unidad_educativa_id' => $unidadA->id, 'rol' => Rol::ESTUDIANTE, 'ci' => '1', 'usuario' => '1',
            'nombres' => 'A', 'apellidos' => 'A', 'password' => bcrypt('x'), 'debe_cambiar_password' => false,
            'estado' => EstadoUsuario::ACTIVO,
        ])->id, 'unidad_educativa_id' => $unidadA->id]);

        Estudiante::create(['user_id' => User::create([
            'unidad_educativa_id' => $unidadB->id, 'rol' => Rol::ESTUDIANTE, 'ci' => '2', 'usuario' => '2',
            'nombres' => 'B', 'apellidos' => 'B', 'password' => bcrypt('x'), 'debe_cambiar_password' => false,
            'estado' => EstadoUsuario::ACTIVO,
        ])->id, 'unidad_educativa_id' => $unidadB->id]);

        TenantContext::set($unidadA->id, false);
        $this->assertSame(1, Estudiante::count());
        $this->assertSame($unidadA->id, Estudiante::first()->unidad_educativa_id);
    }
}
