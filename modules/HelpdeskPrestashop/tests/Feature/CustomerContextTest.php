<?php

namespace Modules\HelpdeskPrestashop\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\HelpdeskPrestashop\Database\Seeders\HelpdeskPrestashopPermissionsSeeder;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CustomerContextTest extends TestCase
{
    use DatabaseTransactions;

    protected string $apiUrl = 'http://localhost:8090/modules/alsernetbridge/api.php';

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(HelpdeskPrestashopPermissionsSeeder::class);

        config([
            'helpdeskprestashop.api_url' => $this->apiUrl,
            'helpdeskprestashop.webhook_secret' => 'test-secret-for-hmac',
            'helpdeskprestashop.cache_ttl' => 300,
            'helpdeskprestashop.http_timeout' => 10,
        ]);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    private function userWithPermission(string ...$permissions): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }

    private function fakeSuccessfulApiResponse(array $overrides = []): void
    {
        Http::fake([
            $this->apiUrl => Http::response(array_merge([
                'ok' => true,
                'data' => [
                    'customer' => [
                        'found' => true,
                        'id' => 1,
                        'firstname' => 'Juan',
                        'lastname' => 'García',
                        'email' => 'juan@example.com',
                        'orders_count' => 5,
                        'last_order_at' => '2024-03-15 10:23:00',
                        'ltv' => 1523.50,
                    ],
                    'orders' => [],
                    'carts' => [],
                ],
            ], $overrides), 200),
        ]);
    }

    private function fakeOrderDetailApiResponse(): void
    {
        Http::fake([
            $this->apiUrl => Http::response([
                'ok' => true,
                'data' => [
                    'id' => 999,
                    'reference' => 'ABCDEF',
                    'lines' => [],
                ],
            ], 200),
        ]);
    }

    private function fakeStartReturnApiResponse(): void
    {
        Http::fake([
            $this->apiUrl => Http::response([
                'ok' => true,
                'data' => ['return_id' => 42, 'status' => 'created'],
            ], 200),
        ]);
    }

    // ─── Customer context ──────────────────────────────────────────────────────

    public function test_authenticated_user_with_permission_can_get_context(): void
    {
        $this->fakeSuccessfulApiResponse();
        $user = $this->userWithPermission('helpdeskprestashop.view');

        $response = $this->actingAs($user)
            ->getJson('/api/helpdeskprestashop/customers/juan@example.com/context');

        $response->assertOk();
        $response->assertJsonPath('customer.found', true);
        $response->assertJsonPath('customer.ordersCount', 5);
    }

    public function test_user_without_permission_returns_403(): void
    {
        // Regression test: previously checked helpdesk.tickets.view (wrong module)
        // Now must check helpdeskprestashop.view (correct)
        Http::fake();
        $user = User::factory()->create(); // no permissions

        $response = $this->actingAs($user)
            ->getJson('/api/helpdeskprestashop/customers/juan@example.com/context');

        $response->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_unauthenticated_returns_401(): void
    {
        Http::fake();

        $response = $this->getJson('/api/helpdeskprestashop/customers/juan@example.com/context');

        $response->assertUnauthorized();
        Http::assertNothingSent();
    }

    public function test_invalid_email_returns_422(): void
    {
        $this->fakeSuccessfulApiResponse();
        $user = $this->userWithPermission('helpdeskprestashop.view');

        $response = $this->actingAs($user)
            ->getJson('/api/helpdeskprestashop/customers/not-a-valid-email/context');

        $response->assertUnprocessable();
    }

    public function test_request_includes_hmac_signature(): void
    {
        $this->fakeSuccessfulApiResponse();
        $user = $this->userWithPermission('helpdeskprestashop.view');

        $this->actingAs($user)
            ->getJson('/api/helpdeskprestashop/customers/juan@example.com/context');

        $recorded = Http::recorded();
        $this->assertNotEmpty($recorded, 'Expected at least one HTTP request to PrestaShop.');

        /** @var Request $sentRequest */
        $sentRequest = $recorded[0][0];

        // Assert timestamp header is present and numeric
        $timestampHeader = $sentRequest->header('X-Alsernet-Timestamp');
        $this->assertNotEmpty($timestampHeader, 'X-Alsernet-Timestamp header must be present.');
        $ts = $timestampHeader[0];
        $this->assertMatchesRegularExpression('/^\d+$/', (string) $ts, 'X-Alsernet-Timestamp must be numeric.');

        $this->assertNotEmpty(
            $sentRequest->header('X-Alsernet-Signature'),
            'X-Alsernet-Signature header must be present.'
        );

        // Verify signature is SHA-256 HMAC of "{timestamp}:{body}"
        $sentBody = (string) $sentRequest->body();
        $expectedSignature = hash_hmac('sha256', $ts.':'.$sentBody, 'test-secret-for-hmac');
        $actualSignature = $sentRequest->header('X-Alsernet-Signature')[0];

        $this->assertSame($expectedSignature, $actualSignature, 'HMAC signature must match.');
    }

    // ─── Refresh endpoint ─────────────────────────────────────────────────────

    public function test_refresh_endpoint_invalidates_cache(): void
    {
        $email = 'juan@example.com';
        $cacheKey = 'prestashop_ctx_'.md5(strtolower(trim($email)));

        // Seed stale data in cache
        Cache::put($cacheKey, ['customer' => ['found' => false], 'orders' => [], 'carts' => []], 300);

        $this->fakeSuccessfulApiResponse();
        $user = $this->userWithPermission('helpdeskprestashop.refresh');

        $response = $this->actingAs($user)
            ->postJson('/api/helpdeskprestashop/customers/'.$email.'/context/refresh');

        $response->assertOk();
        $response->assertJsonPath('customer.found', true);

        // Cache must have been refreshed — new value should be stored
        $cached = Cache::get($cacheKey);
        $this->assertTrue($cached['customer']['found'] ?? false, 'Cache must contain fresh data after refresh.');
    }

    public function test_refresh_without_refresh_permission_returns_403(): void
    {
        Http::fake();
        $user = $this->userWithPermission('helpdeskprestashop.view');

        $response = $this->actingAs($user)
            ->postJson('/api/helpdeskprestashop/customers/juan@example.com/context/refresh');

        $response->assertForbidden();
        Http::assertNothingSent();
    }

    // ─── Order detail ─────────────────────────────────────────────────────────

    public function test_order_detail_returns_data(): void
    {
        $this->fakeOrderDetailApiResponse();
        $user = $this->userWithPermission('helpdeskprestashop.orders.detail.view');

        $response = $this->actingAs($user)
            ->getJson('/api/helpdeskprestashop/orders/999/detail');

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.reference', 'ABCDEF');
    }

    public function test_order_detail_requires_permission(): void
    {
        Http::fake();
        $user = User::factory()->create(); // no permissions

        $response = $this->actingAs($user)
            ->getJson('/api/helpdeskprestashop/orders/999/detail');

        $response->assertForbidden();
        Http::assertNothingSent();
    }

    // ─── Start return ─────────────────────────────────────────────────────────

    public function test_start_return_with_valid_items_succeeds(): void
    {
        $this->fakeStartReturnApiResponse();
        $user = $this->userWithPermission('helpdeskprestashop.orders.return');

        $response = $this->actingAs($user)
            ->postJson('/api/helpdeskprestashop/orders/999/start-return', [
                'items' => [
                    ['order_detail_id' => 101, 'quantity' => 1, 'reason' => 'Producto defectuoso'],
                ],
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.return_id', 42);
    }

    public function test_start_return_with_empty_items_returns_422(): void
    {
        Http::fake();
        $user = $this->userWithPermission('helpdeskprestashop.orders.return');

        $response = $this->actingAs($user)
            ->postJson('/api/helpdeskprestashop/orders/999/start-return', [
                'items' => [],
            ]);

        $response->assertUnprocessable();
        Http::assertNothingSent();
    }

    public function test_start_return_without_items_returns_422(): void
    {
        Http::fake();
        $user = $this->userWithPermission('helpdeskprestashop.orders.return');

        $response = $this->actingAs($user)
            ->postJson('/api/helpdeskprestashop/orders/999/start-return', []);

        $response->assertUnprocessable();
        Http::assertNothingSent();
    }

    public function test_start_return_without_permission_returns_403(): void
    {
        Http::fake();
        $user = User::factory()->create(); // no permissions

        $response = $this->actingAs($user)
            ->postJson('/api/helpdeskprestashop/orders/999/start-return', [
                'items' => [
                    ['order_detail_id' => 101, 'quantity' => 1],
                ],
            ]);

        $response->assertForbidden();
        Http::assertNothingSent();
    }
}
