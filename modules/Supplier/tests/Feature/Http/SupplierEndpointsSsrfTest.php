<?php

namespace Modules\Supplier\Tests\Feature\Http;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Http\Middleware\VerifyCsrfToken;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SupplierEndpointsSsrfTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('users') || ! Schema::hasTable('permissions')) {
            $this->markTestSkipped('Required tables (users, permissions) are not present in the test database.');
        }

        $this->withoutMiddleware(VerifyCsrfToken::class);
        activity()->disableLogging();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    private function userWithSyncConfig(): User
    {
        Permission::firstOrCreate(['name' => 'suppliers.sync.config', 'guard_name' => 'web']);
        $user = User::factory()->create(['available' => true]);
        $user->givePermissionTo('suppliers.sync.config');

        return $user;
    }

    public function test_test_endpoint_rejects_internal_ip_with_422(): void
    {
        Http::fake();

        $this->actingAs($this->userWithSyncConfig())
            ->postJson(route('settings.suppliers.endpoints.test'), [
                'url' => 'http://169.254.169.254/latest/meta-data/',
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        Http::assertNothingSent();
    }

    public function test_test_endpoint_rejects_localhost_with_422(): void
    {
        Http::fake();

        $this->actingAs($this->userWithSyncConfig())
            ->postJson(route('settings.suppliers.endpoints.test'), [
                'url' => 'http://localhost:8080/api-gestion/modelo/',
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        Http::assertNothingSent();
    }

    public function test_test_endpoint_rejects_non_http_scheme(): void
    {
        $this->actingAs($this->userWithSyncConfig())
            ->postJson(route('settings.suppliers.endpoints.test'), [
                'url' => 'ftp://example.com/file',
            ])
            ->assertStatus(422);
    }

    public function test_test_endpoint_allows_public_url(): void
    {
        Http::fake([
            '*' => Http::response(['ok' => true], 200),
        ]);

        $this->actingAs($this->userWithSyncConfig())
            ->postJson(route('settings.suppliers.endpoints.test'), [
                'url' => 'https://8.8.8.8/api-gestion/modelo/',
                'idmodelo' => 'ABC',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}
