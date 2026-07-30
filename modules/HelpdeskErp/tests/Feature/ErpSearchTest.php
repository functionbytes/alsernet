<?php

namespace Modules\HelpdeskErp\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Laravel\Pulse\Pulse;
use Modules\HelpdeskErp\Database\Seeders\HelpdeskErpPermissionsSeeder;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Tests for GET /api/helpdeskErp/customers/search
 *
 * Requires permission: helpdeskerp.view
 * Returns [] when q < 3 chars (no upstream call).
 * Proxies to manager /api/erp/customer/search otherwise.
 */
class ErpSearchTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(Pulse::class, new class
        {
            public function set(string $type, string $key, mixed $value, mixed $timestamp = null): object
            {
                return new \stdClass;
            }

            public function record(mixed ...$args): object
            {
                return new \stdClass;
            }
        });

        config(['helpdeskerp.manager_url' => 'http://manager.test']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(HelpdeskErpPermissionsSeeder::class);

        $this->user = User::factory()->create();
        $this->user->givePermissionTo(['helpdeskerp.view', 'helpdeskerp.prospect.view']);
    }

    // ── Authorization ──────────────────────────────────────────────────────

    public function test_unauthenticated_returns_401(): void
    {
        $this->getJson('/api/helpdeskErp/customers/search?q=test')
            ->assertUnauthorized();
    }

    public function test_user_without_view_permission_returns_403(): void
    {
        $noPermUser = User::factory()->create();

        $this->actingAs($noPermUser, 'sanctum')
            ->getJson('/api/helpdeskErp/customers/search?q=test')
            ->assertForbidden();
    }

    // ── Short query guard ──────────────────────────────────────────────────

    public function test_query_less_than_3_chars_returns_empty_without_upstream_call(): void
    {
        Http::fake(); // Nothing should be called

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/helpdeskErp/customers/search?q=ab')
            ->assertOk()
            ->assertJson(['data' => []]);

        Http::assertNothingSent();
    }

    public function test_empty_query_returns_empty_without_upstream_call(): void
    {
        Http::fake();

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/helpdeskErp/customers/search?q=')
            ->assertOk()
            ->assertJson(['data' => []]);

        Http::assertNothingSent();
    }

    // ── Happy path ─────────────────────────────────────────────────────────

    public function test_valid_query_proxies_to_manager_and_returns_results(): void
    {
        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [
                    ['id' => 1, 'label' => 'Juan', 'email' => 'juan@example.com'],
                    ['id' => 2, 'label' => 'Juana', 'email' => 'juana@example.com'],
                ],
            ]),
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/helpdeskErp/customers/search?q=juan')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.email', 'juan@example.com');
    }

    public function test_exactly_3_char_query_is_forwarded(): void
    {
        Http::fake([
            '*/erp/customer/search*' => Http::response(['data' => []]),
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/helpdeskErp/customers/search?q=abc')
            ->assertOk()
            ->assertJson(['data' => []]);

        Http::assertSentCount(1);
    }

    public function test_search_by_phone_type_includes_type_param(): void
    {
        Http::fake([
            '*/erp/customer/search*' => Http::response(['data' => [['id' => 5, 'label' => 'Test']]]),
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/helpdeskErp/customers/search?q=600123456&type=phone')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'type=phone');
        });
    }

    public function test_manager_error_returns_empty_data(): void
    {
        Http::fake([
            '*/erp/customer/search*' => Http::response([], 500),
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/helpdeskErp/customers/search?q=error')
            ->assertOk()
            ->assertJson(['data' => []]);
    }
}
