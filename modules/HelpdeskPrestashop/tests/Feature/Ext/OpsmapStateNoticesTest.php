<?php

namespace Modules\HelpdeskPrestashop\Tests\Feature\Ext;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Modules\HelpdeskPrestashop\Database\Seeders\HelpdeskPrestashopPermissionsSeeder;
use Modules\HelpdeskPrestashop\Services\Ext\OpsmapStateNoticeService;
use Tests\TestCase;

/**
 * Avisos al cambiar el estado de un pedido desde el chat: pantalla de
 * configuración (Ajustes → Avisos de cambio de estado) y su efecto en el
 * catálogo de estados que usa el workspace de pedido.
 */
class OpsmapStateNoticesTest extends TestCase
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
        ]);

        $this->seed(HelpdeskPrestashopPermissionsSeeder::class);

        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => ['states' => [
            ['id' => 4, 'name' => 'Enviado', 'paid' => 1, 'shipped' => 1, 'delivery' => 1, 'send_email' => 0, 'template' => '', 'invoice' => 1],
            ['id' => 6, 'name' => 'Pedido cancelado', 'paid' => 0, 'shipped' => 0, 'delivery' => 0, 'send_email' => 1, 'template' => 'order_canceled', 'invoice' => 0],
        ]]])]);
    }

    private function user(string ...$permissions): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }

    public function test_screen_requires_statemap_permission(): void
    {
        $this->actingAs($this->user('helpdeskprestashop.orders.view'))
            ->get(route('manager.helpdesk.ps.ext.opsmap.state-notices'))
            ->assertForbidden();

        $this->actingAs($this->user('helpdeskprestashop.statemap.manage'))
            ->get(route('manager.helpdesk.ps.ext.opsmap.state-notices'))
            ->assertOk()
            ->assertSee('Pedido cancelado')
            ->assertSee('Correo · Pedido cancelado')
            ->assertSee('Sin correo');
    }

    public function test_save_keeps_only_rows_that_change_the_default(): void
    {
        $this->actingAs($this->user('helpdeskprestashop.statemap.manage'))
            ->post(route('manager.helpdesk.ps.ext.opsmap.state-notices.update'), ['states' => [
                4 => ['notify' => 'default', 'notice' => '', 'name' => 'Enviado'],
                6 => ['notify' => 'no', 'notice' => 'Confirma con el cliente antes de anular', 'name' => 'Pedido cancelado'],
            ]])
            ->assertRedirect(route('manager.helpdesk.ps.ext.opsmap.state-notices'));

        $all = app(OpsmapStateNoticeService::class)->all();

        $this->assertArrayNotHasKey(4, $all);
        $this->assertFalse($all[6]['notify_default']);
        $this->assertSame('Confirma con el cliente antes de anular', $all[6]['agent_notice']);
    }

    public function test_order_states_endpoint_carries_email_and_configured_notice(): void
    {
        app(OpsmapStateNoticeService::class)->save([
            6 => ['notify' => 'yes', 'notice' => 'Avisa a logística', 'name' => 'Pedido cancelado'],
        ], null);

        $states = collect($this->actingAs($this->user('helpdeskprestashop.orders.view'))
            ->getJson(route('manager.helpdesk.ps.order-states'))
            ->assertOk()
            ->json('states'))->keyBy('id');

        $this->assertSame('Pedido cancelado', $states[6]['template_label']);
        $this->assertTrue($states[6]['notify_default']);
        $this->assertSame('Avisa a logística', $states[6]['agent_notice']);
        $this->assertNull($states[4]['notify_default']);
        $this->assertNull($states[4]['agent_notice']);
    }
}
