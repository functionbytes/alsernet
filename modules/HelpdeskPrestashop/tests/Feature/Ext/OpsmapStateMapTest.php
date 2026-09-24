<?php

namespace Modules\HelpdeskPrestashop\Tests\Feature\Ext;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\CustomerExternalId;
use Modules\HelpdeskPrestashop\Database\Seeders\HelpdeskPrestashopPermissionsSeeder;
use Modules\HelpdeskPrestashop\Events\PsOrderStatusChanged;
use Modules\HelpdeskPrestashop\Models\Ext\OrderlinkLink;
use Modules\HelpdeskPrestashop\Services\Ext\OpsmapStateMapService;
use Tests\TestCase;

/**
 * Pieza 39 "Mapeo de estados": pantalla (permiso, guardado) y aplicación
 * del mapeo al llegar el webhook order.status_changed. El bridge se simula
 * con Http::fake (solo para el catálogo de estados, que es lectura).
 */
class OpsmapStateMapTest extends TestCase
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
            ['id' => 2, 'name' => 'Pago aceptado'],
            ['id' => 4, 'name' => 'Enviado'],
            ['id' => 6, 'name' => 'Cancelado'],
        ]]])]);
    }

    private function manager(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdeskprestashop.statemap.manage');

        return $user;
    }

    public function test_screen_requires_statemap_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('manager.helpdesk.ps.ext.opsmap.state-map'))
            ->assertForbidden();

        $this->actingAs(User::factory()->create())
            ->post(route('manager.helpdesk.ps.ext.opsmap.state-map.update'), ['map' => [4 => 'resolved']])
            ->assertForbidden();
    }

    public function test_screen_lists_prestashop_states(): void
    {
        $this->actingAs($this->manager())
            ->get(route('manager.helpdesk.ps.ext.opsmap.state-map'))
            ->assertOk()
            ->assertSee('Enviado')
            ->assertSee('Guardar mapeo')
            ->assertSee('Crear nota en el ticket al cambiar de estado en la tienda');
    }

    public function test_save_stores_only_states_with_action_and_the_note_flag(): void
    {
        $this->actingAs($this->manager())
            ->post(route('manager.helpdesk.ps.ext.opsmap.state-map.update'), [
                'map' => [2 => 'none', 4 => 'resolved', 6 => 'closed'],
                'names' => [2 => 'Pago aceptado', 4 => 'Enviado', 6 => 'Cancelado'],
                'create_note' => 1,
            ])
            ->assertRedirect(route('manager.helpdesk.ps.ext.opsmap.state-map'));

        $service = app(OpsmapStateMapService::class);
        $this->assertSame('resolved', $service->actionFor(4));
        $this->assertSame('closed', $service->actionFor(6));
        $this->assertSame('none', $service->actionFor(2));
        $this->assertTrue($service->createNote());
        $this->assertFalse(DB::connection(OpsmapStateMapService::CONNECTION)->table(OpsmapStateMapService::TABLE)->where('ps_state_id', 2)->exists());
    }

    public function test_save_rejects_unknown_actions_and_state_ids(): void
    {
        $this->actingAs($this->manager())
            ->post(route('manager.helpdesk.ps.ext.opsmap.state-map.update'), ['map' => [4 => 'delete_everything']])
            ->assertSessionHasErrors('map.4');

        $this->actingAs($this->manager())
            ->post(route('manager.helpdesk.ps.ext.opsmap.state-map.update'), ['map' => ['abc' => 'resolved']])
            ->assertSessionHasErrors('map');
    }

    public function test_status_change_resolves_latest_conversation_and_leaves_a_note(): void
    {
        if (! ConversationStatus::query()->where('slug', 'resolved')->exists()) {
            $this->markTestSkipped('La instalación no tiene estado de conversación con slug resolved.');
        }

        app(OpsmapStateMapService::class)->save([4 => 'resolved'], [4 => 'Enviado'], true, null);

        $customer = Customer::factory()->create(['email' => 'mapeo-'.uniqid().'@example.com']);
        $psId = (string) random_int(900000000, 999999999);
        CustomerExternalId::create(['customer_id' => $customer->id, 'platform' => 'prestashop', 'external_id' => $psId]);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id, 'last_message_at' => now()->subHour(), 'is_spam' => false, 'is_archived' => false]);

        event(new PsOrderStatusChanged(['order_id' => 829575, 'customer_id' => (int) $psId, 'old_status' => 2, 'new_status' => 4]));

        $conversation->refresh()->load('status');
        $this->assertSame('resolved', $conversation->status->slug);

        $note = ConversationItem::query()->where('conversation_id', $conversation->id)->where('is_internal', true)->latest('id')->first();
        $this->assertNotNull($note);
        $this->assertStringContainsString('#829575', $note->body);
        $this->assertStringContainsString('Enviado', $note->body);
    }

    public function test_state_without_action_and_without_note_changes_nothing(): void
    {
        app(OpsmapStateMapService::class)->save([4 => 'resolved'], [], false, null);

        $customer = Customer::factory()->create(['email' => 'mapeo-'.uniqid().'@example.com']);
        $psId = (string) random_int(900000000, 999999999);
        CustomerExternalId::create(['customer_id' => $customer->id, 'platform' => 'prestashop', 'external_id' => $psId]);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id, 'last_message_at' => now()]);
        $statusBefore = $conversation->status_id;

        event(new PsOrderStatusChanged(['order_id' => 1, 'customer_id' => (int) $psId, 'old_status' => 4, 'new_status' => 6]));

        $this->assertSame($statusBefore, $conversation->refresh()->status_id);
        $this->assertFalse(ConversationItem::query()->where('conversation_id', $conversation->id)->where('is_internal', true)->exists());
    }

    public function test_conversation_outside_the_window_is_not_touched(): void
    {
        config(['helpdeskprestashop.ext.opsmap.window_days' => 30]);
        app(OpsmapStateMapService::class)->save([6 => 'closed'], [], true, null);

        $customer = Customer::factory()->create(['email' => 'mapeo-'.uniqid().'@example.com']);
        $psId = (string) random_int(900000000, 999999999);
        CustomerExternalId::create(['customer_id' => $customer->id, 'platform' => 'prestashop', 'external_id' => $psId]);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id, 'last_message_at' => now()->subDays(90)]);
        Conversation::query()->whereKey($conversation->id)->update(['updated_at' => now()->subDays(90)]);
        $statusBefore = $conversation->status_id;

        event(new PsOrderStatusChanged(['order_id' => 2, 'customer_id' => (int) $psId, 'old_status' => 4, 'new_status' => 6]));

        $this->assertSame($statusBefore, $conversation->refresh()->status_id);
        $this->assertFalse(ConversationItem::query()->where('conversation_id', $conversation->id)->exists());
    }

    /*
     | Vínculo pedido ↔ conversación (extensión "orderlink"): si el pedido
     | está ligado, el mapeo va a ESA conversación y no a la más reciente.
     */

    /**
     * @return array{0: Customer, 1: string}
     */
    private function linkedCustomer(): array
    {
        $customer = Customer::factory()->create(['email' => 'mapeo-'.uniqid().'@example.com']);
        $psId = (string) random_int(900000000, 999999999);
        CustomerExternalId::create(['customer_id' => $customer->id, 'platform' => 'prestashop', 'external_id' => $psId]);

        return [$customer, $psId];
    }

    public function test_linked_conversation_wins_over_the_most_recent_one(): void
    {
        app(OpsmapStateMapService::class)->save([4 => 'none'], [4 => 'Enviado'], true, null);
        [$customer, $psId] = $this->linkedCustomer();

        $linked = Conversation::factory()->create(['customer_id' => $customer->id, 'last_message_at' => now()->subDays(3), 'is_spam' => false, 'is_archived' => false]);
        $recent = Conversation::factory()->create(['customer_id' => $customer->id, 'last_message_at' => now()->subMinute(), 'is_spam' => false, 'is_archived' => false]);
        OrderlinkLink::create(['conversation_id' => $linked->id, 'customer_id' => $customer->id, 'ps_order_id' => 777001, 'source' => 'opened']);

        event(new PsOrderStatusChanged(['order_id' => 777001, 'customer_id' => (int) $psId, 'old_status' => 2, 'new_status' => 4]));

        $this->assertTrue(ConversationItem::query()->where('conversation_id', $linked->id)->where('is_internal', true)->where('body', 'like', '%#777001%')->exists());
        $this->assertFalse(ConversationItem::query()->where('conversation_id', $recent->id)->where('is_internal', true)->exists());
    }

    public function test_linked_conversation_changes_status_even_outside_the_window(): void
    {
        if (! ConversationStatus::query()->where('slug', 'resolved')->exists()) {
            $this->markTestSkipped('La instalación no tiene estado de conversación con slug resolved.');
        }

        config(['helpdeskprestashop.ext.opsmap.window_days' => 30]);
        app(OpsmapStateMapService::class)->save([4 => 'resolved'], [4 => 'Enviado'], true, null);
        [$customer, $psId] = $this->linkedCustomer();

        $linked = Conversation::factory()->create(['customer_id' => $customer->id, 'last_message_at' => now()->subDays(90), 'is_spam' => false, 'is_archived' => false]);
        Conversation::query()->whereKey($linked->id)->update(['updated_at' => now()->subDays(90)]);
        $recent = Conversation::factory()->create(['customer_id' => $customer->id, 'last_message_at' => now()->subMinute(), 'is_spam' => false, 'is_archived' => false]);
        $recentStatus = $recent->status_id;
        OrderlinkLink::create(['conversation_id' => $linked->id, 'customer_id' => $customer->id, 'ps_order_id' => 777002, 'source' => 'action']);

        event(new PsOrderStatusChanged(['order_id' => 777002, 'customer_id' => (int) $psId, 'old_status' => 2, 'new_status' => 4]));

        $this->assertSame('resolved', $linked->refresh()->load('status')->status->slug);
        $this->assertSame($recentStatus, $recent->refresh()->status_id);
    }

    public function test_most_recent_of_several_linked_conversations_is_used(): void
    {
        app(OpsmapStateMapService::class)->save([4 => 'none'], [4 => 'Enviado'], true, null);
        [$customer, $psId] = $this->linkedCustomer();

        $older = Conversation::factory()->create(['customer_id' => $customer->id, 'last_message_at' => now()->subDays(5), 'is_spam' => false, 'is_archived' => false]);
        $newer = Conversation::factory()->create(['customer_id' => $customer->id, 'last_message_at' => now()->subDays(2), 'is_spam' => false, 'is_archived' => false]);
        Conversation::factory()->create(['customer_id' => $customer->id, 'last_message_at' => now(), 'is_spam' => false, 'is_archived' => false]);
        OrderlinkLink::create(['conversation_id' => $older->id, 'customer_id' => $customer->id, 'ps_order_id' => 777003, 'source' => 'card_sent']);
        OrderlinkLink::create(['conversation_id' => $newer->id, 'customer_id' => $customer->id, 'ps_order_id' => 777003, 'source' => 'opened']);

        event(new PsOrderStatusChanged(['order_id' => 777003, 'customer_id' => (int) $psId, 'old_status' => 2, 'new_status' => 4]));

        $this->assertTrue(ConversationItem::query()->where('conversation_id', $newer->id)->where('is_internal', true)->exists());
        $this->assertFalse(ConversationItem::query()->where('conversation_id', $older->id)->where('is_internal', true)->exists());
    }

    public function test_link_to_a_conversation_of_another_customer_falls_back_to_the_latest(): void
    {
        app(OpsmapStateMapService::class)->save([4 => 'none'], [4 => 'Enviado'], true, null);
        [$customer, $psId] = $this->linkedCustomer();
        [$stranger] = $this->linkedCustomer();

        $foreign = Conversation::factory()->create(['customer_id' => $stranger->id, 'last_message_at' => now(), 'is_spam' => false, 'is_archived' => false]);
        $own = Conversation::factory()->create(['customer_id' => $customer->id, 'last_message_at' => now()->subHour(), 'is_spam' => false, 'is_archived' => false]);
        OrderlinkLink::create(['conversation_id' => $foreign->id, 'customer_id' => $customer->id, 'ps_order_id' => 777004, 'source' => 'opened']);

        event(new PsOrderStatusChanged(['order_id' => 777004, 'customer_id' => (int) $psId, 'old_status' => 2, 'new_status' => 4]));

        $this->assertFalse(ConversationItem::query()->where('conversation_id', $foreign->id)->where('is_internal', true)->exists());
        $this->assertTrue(ConversationItem::query()->where('conversation_id', $own->id)->where('is_internal', true)->exists());
    }

    public function test_link_of_another_order_does_not_redirect_the_mapping(): void
    {
        app(OpsmapStateMapService::class)->save([4 => 'none'], [4 => 'Enviado'], true, null);
        [$customer, $psId] = $this->linkedCustomer();

        $linkedToOther = Conversation::factory()->create(['customer_id' => $customer->id, 'last_message_at' => now()->subDays(3), 'is_spam' => false, 'is_archived' => false]);
        $recent = Conversation::factory()->create(['customer_id' => $customer->id, 'last_message_at' => now()->subMinute(), 'is_spam' => false, 'is_archived' => false]);
        OrderlinkLink::create(['conversation_id' => $linkedToOther->id, 'customer_id' => $customer->id, 'ps_order_id' => 777099, 'source' => 'opened']);

        event(new PsOrderStatusChanged(['order_id' => 777005, 'customer_id' => (int) $psId, 'old_status' => 2, 'new_status' => 4]));

        $this->assertTrue(ConversationItem::query()->where('conversation_id', $recent->id)->where('is_internal', true)->exists());
        $this->assertFalse(ConversationItem::query()->where('conversation_id', $linkedToOther->id)->where('is_internal', true)->exists());
    }
}
