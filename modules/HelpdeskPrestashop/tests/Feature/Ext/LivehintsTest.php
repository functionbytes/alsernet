<?php

namespace Modules\HelpdeskPrestashop\Tests\Feature\Ext;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\CustomerExternalId;
use Modules\HelpdeskPrestashop\Events\Ext\LivehintsHint;
use Modules\HelpdeskPrestashop\Events\PsBackInStock;
use Modules\HelpdeskPrestashop\Events\PsCartAbandoned;
use Modules\HelpdeskPrestashop\Events\PsOrderCreated;
use Modules\HelpdeskPrestashop\Events\PsOrderReturned;
use Modules\HelpdeskPrestashop\Events\PsOrderStatusChanged;
use Modules\HelpdeskPrestashop\Events\PsPriceDropped;
use Modules\HelpdeskPrestashop\Listeners\Ext\LivehintsBackInStock;
use Modules\HelpdeskPrestashop\Listeners\Ext\LivehintsCartAbandoned;
use Modules\HelpdeskPrestashop\Listeners\Ext\LivehintsOrderCreated;
use Modules\HelpdeskPrestashop\Listeners\Ext\LivehintsOrderReturned;
use Modules\HelpdeskPrestashop\Listeners\Ext\LivehintsOrderStatus;
use Modules\HelpdeskPrestashop\Listeners\Ext\LivehintsPriceDropped;
use Tests\TestCase;

/**
 * Extensión "livehints": avisos en vivo (ps.hint.<tipo>) a la conversación
 * abierta del cliente afectado por un webhook de PrestaShop. Los listeners
 * se llaman directamente (sin pasar por el receptor, que dispararía los
 * demás listeners) y el broadcast se simula con Event::fake. El puente se
 * simula con Http::fake.
 */
class LivehintsTest extends TestCase
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

        Event::fake([LivehintsHint::class]);
    }

    /** @return array{0: Customer, 1: Conversation, 2: string} */
    private function openConversation(bool $open = true): array
    {
        $status = ConversationStatus::query()->where('is_open', $open)->first();
        if ($status === null) {
            $this->markTestSkipped('La instalación no tiene estado de conversación '.($open ? 'abierto' : 'cerrado').'.');
        }

        $customer = Customer::factory()->create(['email' => 'livehints-'.uniqid().'@example.com']);
        $psId = (string) random_int(900000000, 999999999);
        CustomerExternalId::create(['customer_id' => $customer->id, 'platform' => 'prestashop', 'external_id' => $psId]);
        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'status_id' => $status->id,
            'last_message_at' => now(),
        ]);

        return [$customer, $conversation, $psId];
    }

    private function fakeBridge(array $wishlist = []): void
    {
        Http::fake([$this->apiUrl => function ($request) use ($wishlist) {
            $action = $request->data()['action'] ?? '';

            return match ($action) {
                'order.states' => Http::response(['ok' => true, 'data' => ['states' => [['id' => 4, 'name' => 'Enviado']]]]),
                'customer.wishlist' => Http::response(['ok' => true, 'data' => ['items' => $wishlist]]),
                default => Http::response(['ok' => true, 'data' => []]),
            };
        }]);
    }

    public function test_order_created_hints_the_open_conversation_by_prestashop_id(): void
    {
        [, $conversation, $psId] = $this->openConversation();
        $this->fakeBridge();

        app(LivehintsOrderCreated::class)->handle(new PsOrderCreated([
            'order_id' => 555, 'customer_id' => (int) $psId, 'reference' => 'ABCDEF', 'total' => 89.9,
        ]));

        Event::assertDispatched(LivehintsHint::class, fn (LivehintsHint $e) => $e->conversationId === $conversation->id
            && $e->broadcastAs() === 'ps.hint.order_created'
            && $e->data['reference'] === 'ABCDEF'
            && $e->data['total'] === 89.9
            && $e->data['order_id'] === 555);
    }

    public function test_no_hint_when_the_customer_has_no_open_conversation(): void
    {
        [, , $psId] = $this->openConversation(false);
        $this->fakeBridge();

        app(LivehintsOrderCreated::class)->handle(new PsOrderCreated(['order_id' => 556, 'customer_id' => (int) $psId, 'total' => 10]));

        Event::assertNotDispatched(LivehintsHint::class);
    }

    public function test_order_status_uses_the_state_name(): void
    {
        [, $conversation, $psId] = $this->openConversation();
        $this->fakeBridge();

        app(LivehintsOrderStatus::class)->handle(new PsOrderStatusChanged([
            'order_id' => 557, 'customer_id' => (int) $psId, 'old_status' => 2, 'new_status' => 4,
        ]));

        Event::assertDispatched(LivehintsHint::class, fn (LivehintsHint $e) => $e->conversationId === $conversation->id
            && $e->broadcastAs() === 'ps.hint.order_status'
            && $e->data['state_name'] === 'Enviado');
    }

    public function test_order_returned_and_cart_abandoned(): void
    {
        [$customer, $conversation, $psId] = $this->openConversation();
        $this->fakeBridge();

        app(LivehintsOrderReturned::class)->handle(new PsOrderReturned(['return_id' => 9, 'order_id' => 558, 'customer_id' => (int) $psId]));
        app(LivehintsCartAbandoned::class)->handle(new PsCartAbandoned([
            'cart_id' => 77, 'customer_id' => 0, 'email' => $customer->email, 'total' => 150.0, 'items_count' => 2,
            'items' => [['name' => 'Cartuchos'], ['name' => 'Chaleco']],
        ]));

        Event::assertDispatched(LivehintsHint::class, fn (LivehintsHint $e) => $e->conversationId === $conversation->id
            && $e->type === 'order_returned' && $e->data['return_id'] === 9);
        Event::assertDispatched(LivehintsHint::class, fn (LivehintsHint $e) => $e->conversationId === $conversation->id
            && $e->type === 'cart_abandoned' && $e->data['total'] === 150.0 && $e->data['items_count'] === 2);
    }

    public function test_type_toggle_turns_the_hint_off(): void
    {
        [, , $psId] = $this->openConversation();
        $this->fakeBridge();
        config(['helpdeskprestashop.ext.livehints.hints.order_created' => false]);

        app(LivehintsOrderCreated::class)->handle(new PsOrderCreated(['order_id' => 559, 'customer_id' => (int) $psId, 'total' => 10]));

        Event::assertNotDispatched(LivehintsHint::class);
    }

    public function test_same_hint_is_not_repeated_within_the_dedupe_window(): void
    {
        [, , $psId] = $this->openConversation();
        $this->fakeBridge();
        $event = new PsOrderCreated(['order_id' => 560, 'customer_id' => (int) $psId, 'total' => 10]);

        app(LivehintsOrderCreated::class)->handle($event);
        app(LivehintsOrderCreated::class)->handle($event);

        Event::assertDispatchedTimes(LivehintsHint::class, 1);
    }

    public function test_back_in_stock_only_for_customers_with_the_product_in_their_wishlist(): void
    {
        [, $conversation] = $this->openConversation();
        $this->fakeBridge([['id' => 4242, 'name' => 'Escopeta Test', 'price' => 100.0, 'price_with_tax' => 121.0, 'tax_rate' => 21.0]]);

        app(LivehintsBackInStock::class)->handle(new PsBackInStock(['product_id' => 4242, 'stock_quantity' => 3, 'price' => 100.0]));

        Event::assertDispatched(LivehintsHint::class, fn (LivehintsHint $e) => $e->conversationId === $conversation->id
            && $e->type === 'back_in_stock'
            && $e->data['name'] === 'Escopeta Test'
            && $e->data['source'] === 'wishlist');
    }

    public function test_back_in_stock_skips_products_not_in_the_wishlist(): void
    {
        [, $conversation] = $this->openConversation();
        $this->fakeBridge([['id' => 1, 'name' => 'Otro producto']]);

        app(LivehintsBackInStock::class)->handle(new PsBackInStock(['product_id' => 4243, 'stock_quantity' => 3]));

        Event::assertNotDispatched(LivehintsHint::class, fn (LivehintsHint $e) => $e->conversationId === $conversation->id);
    }

    public function test_price_drop_adds_vat_with_the_product_tax_rate(): void
    {
        [, $conversation] = $this->openConversation();
        $this->fakeBridge([['id' => 4244, 'name' => 'Visor Test', 'price' => 80.0, 'price_with_tax' => 96.8, 'tax_rate' => 21.0]]);

        app(LivehintsPriceDropped::class)->handle(new PsPriceDropped([
            'product_id' => 4244, 'old_price' => 100.0, 'new_price' => 80.0, 'drop_percent' => 20.0,
        ]));

        Event::assertDispatched(LivehintsHint::class, fn (LivehintsHint $e) => $e->conversationId === $conversation->id
            && $e->type === 'price_dropped'
            && $e->data['old_price'] === 121.0
            && $e->data['new_price'] === 96.8
            && $e->data['tax_included'] === true);
    }

    public function test_broadcast_goes_to_the_private_conversation_channel(): void
    {
        $event = new LivehintsHint(42, 'order_created', ['order_id' => 1]);

        $this->assertSame('private-helpdesk.conversation.42', $event->broadcastOn()[0]->name);
        $this->assertSame('ps.hint.order_created', $event->broadcastAs());
        $this->assertSame('helpdesk-broadcasts', $event->broadcastQueue());
        $this->assertSame(42, $event->broadcastWith()['conversation_id']);
    }
}
