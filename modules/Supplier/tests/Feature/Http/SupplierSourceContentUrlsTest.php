<?php

namespace Modules\Supplier\Tests\Feature\Http;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Http\Middleware\VerifyCsrfToken;
use Modules\Supplier\Database\Factories\Source\SourceFactory;
use Modules\Supplier\Database\Factories\Supplier\SupplierFactory;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Cubre las URLs de referencia de contenido (páginas del proveedor a
 * consultar antes que la búsqueda web general) gestionadas desde el
 * formulario de crear/editar fuente.
 */
class SupplierSourceContentUrlsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('users') || ! Schema::hasTable('permissions')) {
            $this->markTestSkipped('Required tables (users, permissions) are not present in the test database.');
        }

        activity()->disableLogging();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    private function userWithPermission(): User
    {
        Permission::firstOrCreate(['name' => 'suppliers.sources.manage', 'guard_name' => 'web']);
        $user = User::factory()->create(['available' => true]);
        $user->givePermissionTo('suppliers.sources.manage');

        return $user;
    }

    public function test_create_view_renders_content_urls_section(): void
    {
        $this->skipIfTablesMissing();

        $user = $this->userWithPermission();
        $supplier = SupplierFactory::new()->create();

        $response = $this->actingAs($user)->get(route('settings.suppliers.sources.create', $supplier->uid));

        $response->assertOk();
        $response->assertSee('URLs de referencia para generar contenido');
        $response->assertSee('addContentUrlBtn', false);
    }

    public function test_edit_view_renders_existing_content_urls(): void
    {
        $this->skipIfTablesMissing();

        $user = $this->userWithPermission();
        $supplier = SupplierFactory::new()->create();
        $source = SourceFactory::new()->website()->create(['supplier_id' => $supplier->id]);
        $source->contentUrls()->create(['url' => 'https://proveedor.com/catalogo', 'note' => 'catálogo']);

        $response = $this->actingAs($user)->get(route('settings.suppliers.sources.edit', [$supplier->uid, $source->uid]));

        $response->assertOk();
        $response->assertSee('URLs de referencia para generar contenido');
        $response->assertSee('https://proveedor.com/catalogo', false);
    }

    public function test_store_persists_content_reference_urls(): void
    {
        $this->skipIfTablesMissing();

        $user = $this->userWithPermission();
        $supplier = SupplierFactory::new()->create();

        $response = $this->actingAs($user)->post(
            route('settings.suppliers.sources.store', $supplier->uid),
            [
                'label' => 'Web oficial del proveedor',
                'source_type' => 'website',
                'is_active' => 1,
                'content_urls' => [
                    ['url' => 'https://proveedor.com/catalogo', 'note' => 'catálogo PDF'],
                    ['url' => 'https://proveedor.com/prensa', 'note' => null],
                    ['url' => '', 'note' => 'fila vacía, se ignora'],
                ],
            ]
        );

        $response->assertOk()->assertJson(['success' => true]);

        $source = $supplier->sources()->where('label', 'Web oficial del proveedor')->firstOrFail();

        $this->assertCount(2, $source->contentUrls);
        $this->assertDatabaseHas('supplier_source_content_urls', [
            'source_id' => $source->id,
            'url' => 'https://proveedor.com/catalogo',
            'note' => 'catálogo PDF',
        ]);
        $this->assertDatabaseHas('supplier_source_content_urls', [
            'source_id' => $source->id,
            'url' => 'https://proveedor.com/prensa',
        ]);
    }

    public function test_update_replaces_the_previous_content_url_set(): void
    {
        $this->skipIfTablesMissing();

        $user = $this->userWithPermission();
        $supplier = SupplierFactory::new()->create();
        $source = SourceFactory::new()->website()->create(['supplier_id' => $supplier->id]);
        $source->contentUrls()->create(['url' => 'https://old.example.com', 'note' => 'antigua']);

        $response = $this->actingAs($user)->put(
            route('settings.suppliers.sources.update', [$supplier->uid, $source->uid]),
            [
                'label' => $source->label,
                'source_type' => 'website',
                'is_active' => 1,
                'content_urls' => [
                    ['url' => 'https://new.example.com', 'note' => 'nueva'],
                ],
            ]
        );

        $response->assertOk()->assertJson(['success' => true]);

        $source->refresh();
        $this->assertCount(1, $source->contentUrls);
        $this->assertSame('https://new.example.com', $source->contentUrls->first()->url);
        $this->assertDatabaseMissing('supplier_source_content_urls', ['url' => 'https://old.example.com']);
    }

    private function skipIfTablesMissing(): void
    {
        foreach (['suppliers', 'supplier_sources', 'supplier_source_content_urls'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Required table {$table} is not present in the test database.");
            }
        }
    }
}
