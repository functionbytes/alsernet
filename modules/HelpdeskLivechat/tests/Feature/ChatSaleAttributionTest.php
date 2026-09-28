<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskLivechat\Models\ChatAttributedSale;
use Modules\HelpdeskLivechat\Models\WidgetSession;
use Modules\HelpdeskLivechat\Services\Commerce\ChatSaleAttributionService;
use Modules\HelpdeskLivechat\Tests\Concerns\SeedsOpenConversationStatus;
use Tests\TestCase;

/**
 * Fase 4 live commerce: pedidos atribuidos al chat (último contacto, 30 días).
 */
class ChatSaleAttributionTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsOpenConversationStatus;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private ChatSaleAttributionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedOpenConversationStatus();
        $this->service = app(ChatSaleAttributionService::class);
    }

    private function conversation(string $sessionToken, ?int $cartId = null, ?int $assigneeId = null): Conversation
    {
        WidgetSession::create([
            'session_token' => $sessionToken,
            'current_url' => 'https://shop.example/',
            'cart_id' => $cartId,
            'started_at' => now(),
            'last_activity_at' => now(),
        ]);
        $customer = Customer::create(['email' => 'c'.uniqid().'@example.com', 'name' => 'Cliente']);

        return Conversation::factory()->create([
            'customer_id' => $customer->id,
            'channel' => 'web',
            'assignee_id' => $assigneeId,
            'last_message_at' => now()->subMinutes(10),
            'metadata' => ['widget_session_token' => $sessionToken, 'widget_pubsub_token' => Str::random(32)],
        ]);
    }

    private function orderId(): int
    {
        return random_int(900000000, 999999999);
    }

    public function test_valid_chat_cookie_attributes_the_order(): void
    {
        $conversation = $this->conversation('sess-cookie-'.uniqid());
        $orderId = $this->orderId();

        $sale = $this->service->attribute([
            'order_id' => $orderId,
            'reference' => 'ABCDEF',
            'total' => 57.98,
            'currency' => 'EUR',
            'chat_session' => [
                'conversation_id' => $conversation->id,
                'session_token' => $conversation->metadata['widget_session_token'],
                'touched_at' => now()->subMinutes(5)->timestamp,
                'source' => 'cart',
            ],
        ]);

        $this->assertNotNull($sale);
        $this->assertSame($conversation->id, $sale->conversation_id);
        $this->assertSame('cookie', $sale->matched_by);
        $this->assertTrue($sale->same_session);
        $this->assertTrue($sale->via_bot);
        $this->assertSame('57.98', (string) $sale->total);
    }

    public function test_forged_cookie_for_another_conversation_is_ignored(): void
    {
        $victim = $this->conversation('sess-victim-'.uniqid());

        $sale = $this->service->attribute([
            'order_id' => $this->orderId(),
            'total' => 10,
            'chat_session' => [
                'conversation_id' => $victim->id,
                'session_token' => 'otro-token-inventado',
                'touched_at' => now()->timestamp,
                'source' => 'chat',
            ],
        ]);

        $this->assertNull($sale);
    }

    public function test_cart_seen_in_chat_attributes_gateway_callback_orders(): void
    {
        $cartId = random_int(800000000, 899999999);
        $conversation = $this->conversation('sess-cart-'.uniqid(), $cartId, assigneeId: 7);

        $sale = $this->service->attribute(['order_id' => $this->orderId(), 'cart_id' => $cartId, 'total' => 99.5]);

        $this->assertNotNull($sale);
        $this->assertSame($conversation->id, $sale->conversation_id);
        $this->assertSame('cart', $sale->matched_by);
        $this->assertSame(7, $sale->agent_id);
        $this->assertFalse($sale->via_bot);
    }

    public function test_old_chat_contact_is_outside_the_window(): void
    {
        $conversation = $this->conversation('sess-old-'.uniqid());

        $sale = $this->service->attribute([
            'order_id' => $this->orderId(),
            'chat_session' => [
                'conversation_id' => $conversation->id,
                'session_token' => $conversation->metadata['widget_session_token'],
                'touched_at' => now()->subDays(31)->timestamp,
                'source' => 'chat',
            ],
        ]);

        $this->assertNull($sale);
    }

    public function test_repeated_webhook_is_idempotent(): void
    {
        $cartId = random_int(800000000, 899999999);
        $this->conversation('sess-idem-'.uniqid(), $cartId);
        $payload = ['order_id' => $this->orderId(), 'cart_id' => $cartId, 'total' => 20];

        $first = $this->service->attribute($payload);
        $second = $this->service->attribute($payload);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, ChatAttributedSale::where('order_id', $payload['order_id'])->count());
    }

    public function test_order_without_chat_is_not_attributed(): void
    {
        $this->assertNull($this->service->attribute(['order_id' => $this->orderId(), 'cart_id' => 1, 'total' => 5]));
    }

    public function test_every_order_with_pilot_cookie_is_recorded_once(): void
    {
        $orderId = $this->orderId();
        $payload = ['order_id' => $orderId, 'total' => 42.5, 'chat_pilot' => 'oct8ne', 'chat_pilot_percent' => 30];

        $this->service->attribute($payload);
        $this->service->attribute($payload);
        $this->service->attribute(['order_id' => $this->orderId(), 'total' => 1, 'chat_pilot' => 'otra-cosa']);

        $rows = DB::connection('helpdesk')->table('helpdesk_chat_pilot_orders')->where('order_id', $orderId)->get();
        $this->assertCount(1, $rows);
        $this->assertSame('oct8ne', $rows[0]->bucket);
        $this->assertSame(30, (int) $rows[0]->pilot_percent);
        $this->assertSame(0, DB::connection('helpdesk')->table('helpdesk_chat_pilot_orders')->where('bucket', 'otra-cosa')->count());
    }
}
