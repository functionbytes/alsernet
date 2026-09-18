<?php

namespace Modules\Document\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Document\Entities\DocumentPermission;
use Modules\Document\Entities\DocumentValidatorGroup;
use Modules\Document\Providers\DocumentsServiceProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * El Gate::before que resuelve los permisos propios de Document.
 *
 * Es un gancho GLOBAL: se ejecuta en cada can() de toda la aplicación, no solo
 * en las pantallas de este módulo. Por eso importan dos cosas por igual —
 * que conceda lo que debe, y que no opine sobre abilities ajenas.
 *
 * En sep-2026 dejó de preguntar a la base de datos si el ability es suyo (un
 * SELECT EXISTS por cada comprobación de permiso de cualquier pantalla: 95 de
 * las 171 consultas de la bandeja de conversaciones) y pasó a resolverlo con
 * la lista cacheada. Estos tests fijan que el comportamiento no cambió.
 */
class DocumentPermissionGateTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // La lista se memoiza por proceso: entre tests hay que soltarla o el
        // permiso creado aquí no existiría para el gate.
        DocumentsServiceProvider::forgetPermissionNames();
    }

    protected function tearDown(): void
    {
        DocumentsServiceProvider::forgetPermissionNames();

        parent::tearDown();
    }

    public function test_concede_el_permiso_a_quien_esta_en_un_grupo_que_lo_tiene(): void
    {
        $permiso = DocumentPermission::firstOrCreate(['name' => 'gate-test-aprobar'], ['label' => 'Aprobar (prueba)', 'description' => 'Prueba del gate']);
        $grupo = DocumentValidatorGroup::create([
            'name' => 'Grupo de prueba del gate',
            'key' => 'gate-test-grupo',
        ]);
        $grupo->permissions()->sync([$permiso->id]);

        $user = User::factory()->create();
        $grupo->users()->sync([$user->id]);

        DocumentsServiceProvider::forgetPermissionNames();

        $this->assertTrue($user->can('gate-test-aprobar'));
    }

    public function test_lo_niega_a_quien_no_esta_en_ningun_grupo(): void
    {
        DocumentPermission::firstOrCreate(['name' => 'gate-test-denegar'], ['label' => 'Denegar (prueba)', 'description' => 'Prueba del gate']);
        DocumentsServiceProvider::forgetPermissionNames();

        $user = User::factory()->create();

        $this->assertFalse($user->can('gate-test-denegar'));
    }

    public function test_no_opina_sobre_abilities_que_no_son_suyas(): void
    {
        // Lo que este gancho NUNCA debe hacer es responder por permisos de
        // otros módulos: devolver false ahí cortaría la cadena y denegaría
        // cosas que sí están concedidas por Spatie.
        $user = User::factory()->create();
        $user->givePermissionTo(
            Permission::firstOrCreate(['name' => 'gate-test-ajeno', 'guard_name' => 'web'])
        );

        $this->assertTrue($user->can('gate-test-ajeno'));
    }

    public function test_una_ability_inventada_sigue_denegada(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->can('esto-no-existe-en-ningun-sitio'));
    }

    public function test_un_permiso_nuevo_entra_en_la_lista_sin_esperar_al_ttl(): void
    {
        // La lista se cachea para no consultar la tabla en cada can(), pero el
        // modelo la invalida en su saved()/deleted(): un permiso recién creado
        // tiene que existir para el gate en el acto, no dentro de diez minutos.
        $antes = count(DocumentsServiceProvider::documentPermissionNames());

        $permiso = DocumentPermission::firstOrCreate(
            ['name' => 'gate-test-nuevo'],
            ['label' => 'Nuevo (prueba)', 'description' => 'Prueba del gate']
        );

        $this->assertContains('gate-test-nuevo', DocumentsServiceProvider::documentPermissionNames());
        $this->assertCount($antes + 1, DocumentsServiceProvider::documentPermissionNames());

        // Y al borrarlo, desaparece igual de rápido.
        $permiso->delete();

        $this->assertNotContains('gate-test-nuevo', DocumentsServiceProvider::documentPermissionNames());
    }
}
