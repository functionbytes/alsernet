<?php

namespace Modules\HelpdeskPrestashop\Tests\Feature\Ext;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Modules\HelpdeskPrestashop\Database\Seeders\HelpdeskPrestashopPermissionsSeeder;
use Tests\TestCase;

/**
 * Pieza 34 · Promociones de la tienda. GET /ps/ext/promos/shop: lectura sin
 * cliente, con caché compartida y los filtros de "pública" que viajan al
 * puente (multiuso + lista de exclusión).
 */
class PromosShopTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private string $apiUrl = 'https://ps.test/modules/alsernetbridge/api.php';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'array',
            'helpdeskprestashop.api_url' => $this->apiUrl,
            'helpdeskprestashop.webhook_secret' => 'test-secret-for-hmac',
            'helpdeskprestashop.ext.promos.shop.min_quantity' => 2,
            'helpdeskprestashop.ext.promos.shop.exclude_codes' => ['ALV-PEDIDOPRUEBA'],
        ]);

        $this->seed(HelpdeskPrestashopPermissionsSeeder::class);
    }

    private function viewer(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdeskprestashop.view');

        return $user;
    }

    private function bridgeData(): array
    {
        return [
            'promotions' => [[
                'id' => 5, 'code' => 'OTONO20', 'name' => 'Otoño', 'type' => 'percent', 'reduction_percent' => 20.0,
                'reduction_amount' => 0.0, 'free_shipping' => false, 'minimum_amount' => 0.0,
                'date_to' => now()->addDays(20)->format('Y-m-d H:i:s'), 'restrictions' => ['Sin envío gratis'],
            ]],
            'automatic' => [['id' => 81, 'name' => 'Regalo chaleco', 'description' => 'SAC', 'date_to' => null]],
            'min_quantity' => 2,
        ];
    }

    public function test_user_without_view_permission_is_forbidden(): void
    {
        Http::fake();

        $this->actingAs(User::factory()->create())
            ->getJson(route('manager.helpdesk.ps.ext.promos.shop'))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_lists_promotions_and_sends_the_public_filters_to_the_bridge(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => $this->bridgeData()])]);

        $this->actingAs($this->viewer())
            ->getJson(route('manager.helpdesk.ps.ext.promos.shop'))
            ->assertOk()
            ->assertJsonPath('promotions.0.code', 'OTONO20')
            ->assertJsonPath('automatic.0.id', 81);

        Http::assertSent(function (Request $request) {
            $data = $request->data();

            return ($data['action'] ?? null) === 'promos.shop_list'
                && ($data['min_quantity'] ?? null) === 2
                && ($data['exclude_codes'] ?? null) === ['ALV-PEDIDOPRUEBA']
                // Lectura: sin clave de idempotencia.
                && ! $request->hasHeader('X-Alsernet-Idempotency-Key');
        });
    }

    public function test_list_is_cached_between_calls_and_fresh_bypasses_it(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => $this->bridgeData()])]);
        $user = $this->viewer();

        $this->actingAs($user)->getJson(route('manager.helpdesk.ps.ext.promos.shop'))->assertOk();
        $this->actingAs($user)->getJson(route('manager.helpdesk.ps.ext.promos.shop'))->assertOk();
        Http::assertSentCount(1);

        $this->actingAs($user)->getJson(route('manager.helpdesk.ps.ext.promos.shop', ['fresh' => 1]))->assertOk();
        Http::assertSentCount(2);
    }

    public function test_bridge_down_is_503(): void
    {
        Http::fake([$this->apiUrl => Http::response('boom', 500)]);

        $this->actingAs($this->viewer())
            ->getJson(route('manager.helpdesk.ps.ext.promos.shop'))
            ->assertStatus(503)
            ->assertJsonPath('success', false);
    }
}
