<?php

namespace Modules\HelpdeskPrestashop\Tests\Feature\Ext;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Database\Seeders\HelpdeskPrestashopPermissionsSeeder;
use Modules\HelpdeskPrestashop\Models\Ext\OrderlinkLink;
use Tests\TestCase;

/**
 * Extensión "orderlink": vínculo pedido de PrestaShop ↔ conversación.
 * Nada de esto llama al bridge: no hace falta Http::fake salvo para no
 * salir a la red por accidente.
 */
class OrderlinkTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);

        // La policy de la conversación consulta permisos helpdesk.*; sin
        // sembrarlos Spatie lanza PermissionDoesNotExist en vez de denegar.
        $this->seed(PermissionsSeeder::class);
        $this->seed(HelpdeskPrestashopPermissionsSeeder::class);
    }

    private function agent(bool $canUpdate = true): User
    {
        $user = User::factory()->create();
        $perms = ['helpdeskprestashop.orders.view', 'helpdesk.conversations.view', 'helpdesk.conversations.view-all'];
        if ($canUpdate) {
            $perms[] = 'helpdesk.conversations.update';
        }
        $user->givePermissionTo($perms);

        return $user;
    }

    /**
     * @return array{0: Customer, 1: Conversation}
     */
    private function conversation(): array
    {
        $customer = Customer::factory()->create(['email' => 'orderlink-'.uniqid().'@example.com']);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id, 'last_message_at' => now()]);

        return [$customer, $conversation];
    }

    public function test_store_requires_orders_permission_and_conversation_access(): void
    {
        [, $conversation] = $this->conversation();

        $this->actingAs(User::factory()->create())
            ->postJson(route('manager.helpdesk.ps.ext.orderlink.store', $conversation->id), ['ps_order_id' => 10, 'source' => 'opened'])
            ->assertForbidden();

        $noOrders = User::factory()->create();
        $noOrders->givePermissionTo(['helpdesk.conversations.view', 'helpdesk.conversations.view-all']);
        $this->actingAs($noOrders)
            ->postJson(route('manager.helpdesk.ps.ext.orderlink.store', $conversation->id), ['ps_order_id' => 10, 'source' => 'opened'])
            ->assertForbidden();

        $this->assertFalse(OrderlinkLink::query()->where('conversation_id', $conversation->id)->exists());
    }

    public function test_store_records_the_link_once_and_only_upgrades_the_source(): void
    {
        [$customer, $conversation] = $this->conversation();
        $agent = $this->agent();
        $url = route('manager.helpdesk.ps.ext.orderlink.store', $conversation->id);

        $this->actingAs($agent)
            ->postJson($url, ['ps_order_id' => 829575, 'ps_order_reference' => '#XKBKNABJK', 'source' => 'opened'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.reference', 'XKBKNABJK')
            ->assertJsonPath('data.source', 'opened');

        $this->actingAs($agent)->postJson($url, ['ps_order_id' => 829575, 'source' => 'card_sent'])->assertOk();
        // Volver a abrirlo no baja la fuente.
        $this->actingAs($agent)->postJson($url, ['ps_order_id' => 829575, 'source' => 'opened'])->assertOk();

        $links = OrderlinkLink::query()->where('conversation_id', $conversation->id)->get();
        $this->assertCount(1, $links);
        $this->assertSame('card_sent', $links[0]->source);
        $this->assertSame('XKBKNABJK', $links[0]->ps_order_reference);
        $this->assertSame($customer->id, $links[0]->customer_id);
        $this->assertSame($agent->id, $links[0]->linked_by);
    }

    public function test_browser_cannot_claim_the_action_source_or_send_a_bad_reference(): void
    {
        [, $conversation] = $this->conversation();
        $url = route('manager.helpdesk.ps.ext.orderlink.store', $conversation->id);

        $this->actingAs($this->agent())
            ->postJson($url, ['ps_order_id' => 5, 'source' => 'action'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('source');

        $this->actingAs($this->agent())
            ->postJson($url, ['ps_order_id' => 5, 'source' => 'opened', 'ps_order_reference' => '<b>x</b>'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('ps_order_reference');
    }

    public function test_index_lists_links_of_that_conversation_only(): void
    {
        [$customer, $conversation] = $this->conversation();
        $other = Conversation::factory()->create(['customer_id' => $customer->id]);
        OrderlinkLink::create(['conversation_id' => $conversation->id, 'customer_id' => $customer->id, 'ps_order_id' => 11, 'ps_order_reference' => 'AAA', 'source' => 'opened']);
        OrderlinkLink::create(['conversation_id' => $other->id, 'customer_id' => $customer->id, 'ps_order_id' => 12, 'source' => 'opened']);

        $this->actingAs($this->agent())
            ->getJson(route('manager.helpdesk.ps.ext.orderlink.index', $conversation->id))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.ps_order_id', 11)
            ->assertJsonPath('data.0.reference', 'AAA')
            ->assertJsonPath('data.0.source_label', 'Abierto en el workspace')
            ->assertJsonPath('can_unlink', true);
    }

    public function test_unlink_needs_update_and_is_scoped_to_the_conversation(): void
    {
        [$customer, $conversation] = $this->conversation();
        $other = Conversation::factory()->create(['customer_id' => $customer->id]);
        $link = OrderlinkLink::create(['conversation_id' => $conversation->id, 'customer_id' => $customer->id, 'ps_order_id' => 21, 'source' => 'opened']);
        $foreign = OrderlinkLink::create(['conversation_id' => $other->id, 'customer_id' => $customer->id, 'ps_order_id' => 22, 'source' => 'opened']);

        $this->actingAs($this->agent(false))
            ->postJson(route('manager.helpdesk.ps.ext.orderlink.destroy', [$conversation->id, $link->id]))
            ->assertForbidden();

        // Un vínculo de otra conversación no se borra desde esta.
        $this->actingAs($this->agent())
            ->postJson(route('manager.helpdesk.ps.ext.orderlink.destroy', [$conversation->id, $foreign->id]))
            ->assertNotFound();
        $this->assertTrue(OrderlinkLink::query()->whereKey($foreign->id)->exists());

        $this->actingAs($this->agent())
            ->postJson(route('manager.helpdesk.ps.ext.orderlink.destroy', [$conversation->id, $link->id]))
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->assertFalse(OrderlinkLink::query()->whereKey($link->id)->exists());
    }

    public function test_linking_does_not_show_up_in_the_store_action_audit(): void
    {
        [, $conversation] = $this->conversation();

        $agent = $this->agent();
        $this->actingAs($agent)
            ->postJson(route('manager.helpdesk.ps.ext.orderlink.store', $conversation->id), ['ps_order_id' => 31, 'source' => 'opened'])
            ->assertOk();

        // Acotado al agente del test: la BD de dev puede tener filas reales de
        // antes de que el vínculo se excluyera de la auditoría.
        $this->assertDatabaseMissing('activity_log', [
            'log_name' => 'helpdeskprestashop',
            'description' => 'ps.ext.orderlink.store',
            'causer_id' => $agent->id,
        ], config('activitylog.database_connection') ?: null);
    }

    /**
     * Ruta de escritura de prueba con {customer} y {order}, con el mismo
     * nombre de familia que las reales, para no depender del bridge.
     */
    private function fakeWriteRoute(bool $success = true): string
    {
        Route::middleware(['web', 'auth'])
            ->post('/panel/helpdesk/customers/{customer}/ps/orders/{order}/orderlink-probe', fn (Customer $customer, int $order) => response()->json(['success' => $success]))
            ->name('manager.helpdesk.ps.orders.orderlink-probe');
        Route::getRoutes()->refreshNameLookups();

        return 'manager.helpdesk.ps.orders.orderlink-probe';
    }

    public function test_write_action_with_conversation_header_links_the_order(): void
    {
        [$customer, $conversation] = $this->conversation();
        $route = $this->fakeWriteRoute();

        $this->actingAs($this->agent())
            ->withHeader('X-Ps-Conversation', (string) $conversation->id)
            ->postJson(route($route, [$customer->id, 4455]))
            ->assertOk();

        $link = OrderlinkLink::query()->where('conversation_id', $conversation->id)->where('ps_order_id', 4455)->first();
        $this->assertNotNull($link);
        $this->assertSame('action', $link->source);
    }

    public function test_write_action_ignores_conversation_of_another_customer_and_failed_actions(): void
    {
        [$customer] = $this->conversation();
        [, $foreignConversation] = $this->conversation();
        $route = $this->fakeWriteRoute();

        $this->actingAs($this->agent())
            ->withHeader('X-Ps-Conversation', (string) $foreignConversation->id)
            ->postJson(route($route, [$customer->id, 4456]))
            ->assertOk();

        $this->assertFalse(OrderlinkLink::query()->where('ps_order_id', 4456)->exists());

        [$customer2, $conversation2] = $this->conversation();
        $failed = $this->fakeWriteRoute(false);
        $this->actingAs($this->agent())
            ->withHeader('X-Ps-Conversation', (string) $conversation2->id)
            ->postJson(route($failed, [$customer2->id, 4457]))
            ->assertOk();

        $this->assertFalse(OrderlinkLink::query()->where('ps_order_id', 4457)->exists());
    }
}
