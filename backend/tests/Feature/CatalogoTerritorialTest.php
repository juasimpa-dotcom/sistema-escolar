<?php

namespace Tests\Feature;

use App\Enums\EstadoUnidad;
use App\Enums\EstadoUsuario;
use App\Enums\Rol;
use App\Enums\Sexo;
use App\Enums\TipoUnidad;
use App\Models\DistritoMunicipal;
use App\Models\Macrodistrito;
use App\Models\Municipio;
use App\Models\UnidadEducativa;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogoTerritorialTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::limpiar();
        parent::tearDown();
    }

    private function superadmin(string $ci = '10000001'): User
    {
        return User::create([
            'unidad_educativa_id' => null,
            'rol' => Rol::SUPERADMIN,
            'ci' => $ci,
            'usuario' => $ci,
            'nombres' => 'Super',
            'apellidos' => 'Admin',
            'password' => 'secret123',
            'debe_cambiar_password' => false,
            'estado' => EstadoUsuario::ACTIVO,
        ]);
    }

    private function usuarioUnidad(UnidadEducativa $unidad, Rol $rol, string $ci): User
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

    private function unidad(string $codigo, array $extra = []): UnidadEducativa
    {
        return UnidadEducativa::create(array_merge([
            'nombre' => "Unidad {$codigo}",
            'codigo' => $codigo,
            'estado' => EstadoUnidad::ACTIVA,
        ], $extra));
    }

    private function territorio(): array
    {
        $municipio = Municipio::create(['nombre' => 'La Paz']);

        $macroA = Macrodistrito::create([
            'municipio_id' => $municipio->id,
            'codigo' => 'MD1',
            'nombre' => 'Cotahuma',
            'tipo' => 'urbano',
        ]);

        $macroB = Macrodistrito::create([
            'municipio_id' => $municipio->id,
            'codigo' => 'MD8',
            'nombre' => 'Zongo',
            'tipo' => 'rural',
        ]);

        $distritoA = DistritoMunicipal::create([
            'macrodistrito_id' => $macroA->id,
            'numero' => 1,
            'nombre' => 'Distrito 1',
        ]);

        $distritoB = DistritoMunicipal::create([
            'macrodistrito_id' => $macroB->id,
            'numero' => 24,
            'nombre' => 'Distrito 24',
        ]);

        return [$macroA, $macroB, $distritoA, $distritoB];
    }

    public function test_superadmin_crea_y_edita_macrodistrito(): void
    {
        $admin = $this->superadmin();
        $municipio = Municipio::create(['nombre' => 'La Paz']);

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/macrodistritos', [
            'municipio_id' => $municipio->id,
            'codigo' => 'MD5',
            'nombre' => 'Sur',
            'tipo' => 'urbano',
        ])->assertCreated();

        $this->assertDatabaseHas('macrodistritos', ['codigo' => 'MD5', 'nombre' => 'Sur']);

        $id = Macrodistrito::where('codigo', 'MD5')->first()->id;

        $this->actingAs($admin, 'sanctum')->putJson("/api/v1/macrodistritos/{$id}", [
            'codigo' => 'MD5',
            'nombre' => 'Sur (corregido)',
            'tipo' => 'urbano',
        ])->assertOk();

        $this->assertDatabaseHas('macrodistritos', ['id' => $id, 'nombre' => 'Sur (corregido)']);
    }

    public function test_no_superadmin_no_puede_administrar_catalogo(): void
    {
        $unidad = $this->unidad('A');
        $director = $this->usuarioUnidad($unidad, Rol::DIRECTOR, '20000001');

        $this->actingAs($director, 'sanctum')->getJson('/api/v1/macrodistritos')->assertForbidden();

        $this->actingAs($director, 'sanctum')->postJson('/api/v1/distritos', [
            'numero' => 2,
            'nombre' => 'Distrito 2',
        ])->assertForbidden();
    }

    public function test_catalogos_planos_para_filtros(): void
    {
        [$macroA, $macroB, $distritoA] = $this->territorio();
        $admin = $this->superadmin();

        $macros = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/catalogos/macrodistritos');
        $macros->assertOk();
        $this->assertSame(['MD1', 'MD8'], collect($macros->json())->pluck('codigo')->all());

        $todos = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/catalogos/distritos');
        $todos->assertOk();
        $this->assertCount(2, $todos->json());

        $filtrados = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/catalogos/distritos?macrodistrito_id='.$macroA->id);
        $filtrados->assertOk();
        $this->assertCount(1, $filtrados->json());
        $this->assertSame($distritoA->id, $filtrados->json()[0]['id']);

        $vacio = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/catalogos/distritos?macrodistrito_id='.$macroB->id);
        $this->assertCount(1, $vacio->json());
    }

    public function test_crear_unidad_con_vinculo_territorial(): void
    {
        [, , $distritoA] = $this->territorio();
        $macroA = Macrodistrito::where('codigo', 'MD1')->first();
        $admin = $this->superadmin();

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/unidades', [
            'nombre' => 'Unidad Norte',
            'codigo' => 'UE-NORTE',
            'direccion' => 'Av. Circunvalación 100',
            'zona' => 'Zona Periférica',
            'tipo' => TipoUnidad::CONVENIO->value,
            'macrodistrito_id' => $macroA->id,
            'distrito_municipal_id' => $distritoA->id,
            'director' => [
                'ci' => '30000001',
                'nombres' => 'Ana',
                'apellidos' => 'Torres',
            ],
        ])->assertCreated();

        $unidad = UnidadEducativa::where('codigo', 'UE-NORTE')->first();

        $this->assertSame($macroA->id, $unidad->macrodistrito_id);
        $this->assertSame($distritoA->id, $unidad->distrito_municipal_id);
        $this->assertSame(TipoUnidad::CONVENIO, $unidad->tipo);
        $this->assertSame('Zona Periférica', $unidad->zona);
    }

    public function test_rechaza_distrito_de_otro_macrodistrito(): void
    {
        [, $macroB, $distritoA] = $this->territorio();
        $admin = $this->superadmin();

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/unidades', [
            'nombre' => 'Unidad Sur',
            'codigo' => 'UE-SUR',
            'macrodistrito_id' => $macroB->id,
            'distrito_municipal_id' => $distritoA->id,
            'director' => ['ci' => '30000002', 'nombres' => 'Luis', 'apellidos' => 'Ríos'],
        ])->assertStatus(422);
    }

    public function test_listado_de_unidades_filtrado_por_territorio(): void
    {
        [$macroA, $macroB, $distritoA, $distritoB] = $this->territorio();

        $this->unidad('UE-1', [
            'macrodistrito_id' => $macroA->id,
            'distrito_municipal_id' => $distritoA->id,
            'tipo' => TipoUnidad::FISCAL->value,
            'zona' => 'Zona A',
        ]);
        $this->unidad('UE-2', [
            'macrodistrito_id' => $macroB->id,
            'distrito_municipal_id' => $distritoB->id,
            'tipo' => TipoUnidad::PRIVADA->value,
        ]);

        $admin = $this->superadmin();

        $porMacro = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/unidades?macrodistrito_id='.$macroA->id);
        $porMacro->assertOk();
        $this->assertSame(['UE-1'], collect($porMacro->json('data'))->pluck('codigo')->all());

        $porDistrito = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/unidades?distrito_municipal_id='.$distritoB->id);
        $this->assertSame(['UE-2'], collect($porDistrito->json('data'))->pluck('codigo')->all());

        $porTipo = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/unidades?tipo=privada');
        $this->assertSame(['UE-2'], collect($porTipo->json('data'))->pluck('codigo')->all());

        $porBusqueda = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/unidades?buscar=ue-1');
        $this->assertSame(['UE-1'], collect($porBusqueda->json('data'))->pluck('codigo')->all());

        // Relaciones incluidas para los badges
        $detallada = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/unidades?buscar=UE-1');
        $this->assertSame('MD1', $detallada->json('data.0.macrodistrito.codigo'));
        $this->assertSame(1, $detallada->json('data.0.distrito.numero'));
    }

    public function test_crud_de_distrito_municipal(): void
    {
        [$macroA] = $this->territorio();
        $admin = $this->superadmin();

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/distritos', [
            'macrodistrito_id' => $macroA->id,
            'numero' => 2,
            'nombre' => 'Distrito 2',
        ])->assertCreated();

        $distrito = DistritoMunicipal::where('numero', 2)->first();

        $this->actingAs($admin, 'sanctum')->putJson("/api/v1/distritos/{$distrito->id}", [
            'macrodistrito_id' => $macroA->id,
            'numero' => 2,
            'nombre' => 'Distrito 2 (corregido)',
        ])->assertOk();

        $this->assertDatabaseHas('distritos_municipales', ['id' => $distrito->id, 'nombre' => 'Distrito 2 (corregido)']);

        // No se puede eliminar un distrito asignado a una unidad
        $this->unidad('UE-X', ['distrito_municipal_id' => $distrito->id]);

        $this->actingAs($admin, 'sanctum')->deleteJson("/api/v1/distritos/{$distrito->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('distritos_municipales', ['id' => $distrito->id]);
    }

    public function test_no_elimina_macrodistrito_en_uso(): void
    {
        [$macroA] = $this->territorio();
        $admin = $this->superadmin();

        $this->unidad('UE-Y', ['macrodistrito_id' => $macroA->id]);

        $this->actingAs($admin, 'sanctum')->deleteJson("/api/v1/macrodistritos/{$macroA->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('macrodistritos', ['id' => $macroA->id]);
    }

    public function test_actualiza_vinculo_territorial_de_unidad(): void
    {
        [$macroA, $macroB, $distritoA, $distritoB] = $this->territorio();
        $unidad = $this->unidad('UE-Z', ['macrodistrito_id' => $macroA->id]);
        $admin = $this->superadmin();

        $this->actingAs($admin, 'sanctum')->putJson("/api/v1/unidades/{$unidad->id}", [
            'nombre' => $unidad->nombre,
            'codigo' => $unidad->codigo,
            'macrodistrito_id' => $macroB->id,
            'distrito_municipal_id' => $distritoB->id,
            'tipo' => TipoUnidad::PRIVADA->value,
            'zona' => 'Zona Rural',
        ])->assertOk();

        $unidad->refresh();

        $this->assertSame($macroB->id, $unidad->macrodistrito_id);
        $this->assertSame($distritoB->id, $unidad->distrito_municipal_id);
        $this->assertSame(TipoUnidad::PRIVADA, $unidad->tipo);

        // Solo el distrito: el macrodistrito se deriva de él
        $this->actingAs($admin, 'sanctum')->putJson("/api/v1/unidades/{$unidad->id}", [
            'nombre' => $unidad->nombre,
            'codigo' => $unidad->codigo,
            'distrito_municipal_id' => $distritoA->id,
        ])->assertOk();

        $unidad->refresh();
        $this->assertSame($macroA->id, $unidad->macrodistrito_id);
    }

    public function test_sexos_se_guardan_en_users(): void
    {
        $unidad = $this->unidad('UE-S');
        $user = $this->usuarioUnidad($unidad, Rol::PROFESOR, '60000077');

        $user->update(['sexo' => Sexo::FEMENINO]);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'sexo' => 'F']);
    }
}
