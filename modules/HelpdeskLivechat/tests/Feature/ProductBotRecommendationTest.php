<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskLivechat\Database\Factories\WebFactory;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Modules\HelpdeskLivechat\Tests\Concerns\SeedsOpenConversationStatus;
use Tests\Concerns\SeedsHelpdeskRoles;
use Tests\TestCase;

/**
 * Bot pregunta→productos por la ruta real del widget: al enviar un mensaje de
 * texto, si el canal tiene enable_product_search + feed, se publica un carrusel.
 */
class ProductBotRecommendationTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsHelpdeskRoles;
    use SeedsOpenConversationStatus;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private const FEED_URL = 'https://shop.example/feed.json';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedHelpdeskRoles();
        $this->seedOpenConversationStatus();

        Http::fake([self::FEED_URL => Http::response([
            ['id' => '1', 'title' => 'Zapatillas running', 'price' => 90, 'image_url' => 'https://x/1.jpg'],
            ['id' => '2', 'title' => 'Botas montaña', 'price' => 120],
        ], 200)]);
    }

    private function makeConversation(Web $web): Conversation
    {
        $customer = Customer::factory()->create();
        $inbox = Inbox::firstOrCreate(
            ['channel_type' => 'web', 'channel_id' => $web->id],
            ['uid' => (string) Str::uuid(), 'name' => 'Bot Inbox', 'is_active' => true]
        );

        return Conversation::factory()->create([
            'customer_id' => $customer->id,
            'inbox_id' => $inbox->id,
            'channel' => 'web',
            'metadata' => ['widget_pubsub_token' => 'bot_token_value'],
        ]);
    }

    private function sendText(Conversation $conversation, string $text): void
    {
        $this->withHeaders(['X-Conversation-Token' => 'bot_token_value'])->postJson(
            route('helpdesk-livechat.widget.conversation.messages.send', $conversation->id),
            ['content' => $text]
        )->assertOk();
    }

    public function test_message_triggers_product_carousel_when_bot_enabled(): void
    {
        $web = WebFactory::new()->create([
            'enable_product_search' => true,
            'product_feed_url' => self::FEED_URL,
        ]);
        $conversation = $this->makeConversation($web);

        $this->sendText($conversation, 'busco zapatillas para correr');

        $carousel = ConversationItem::on('helpdesk')
            ->where('conversation_id', $conversation->id)
            ->where('type', 'product_carousel')
            ->first();

        $this->assertNotNull($carousel, 'El bot debería haber publicado un carrusel');
        $this->assertNotEmpty($carousel->metadata['products']);
        $this->assertTrue((bool) $carousel->metadata['is_bot']);
    }

    public function test_widget_get_messages_returns_product_carousel(): void
    {
        $web = WebFactory::new()->create([
            'enable_product_search' => true,
            'product_feed_url' => self::FEED_URL,
        ]);
        $conversation = $this->makeConversation($web);

        $this->sendText($conversation, 'busco zapatillas');

        $response = $this->withHeaders(['X-Conversation-Token' => 'bot_token_value'])->getJson(
            route('helpdesk-livechat.widget.conversation.messages.index', $conversation->id)
        )->assertOk();

        $carousel = collect($response->json('data.messages'))
            ->firstWhere('content_type', 'products');

        $this->assertNotNull($carousel, 'getMessages debería exponer el carrusel');
        $this->assertSame('outgoing', $carousel['message_type']);
        $this->assertNotEmpty($carousel['products']);
    }

    public function test_no_carousel_when_bot_disabled(): void
    {
        $web = WebFactory::new()->create([
            'enable_product_search' => false,
            'product_feed_url' => self::FEED_URL,
        ]);
        $conversation = $this->makeConversation($web);

        $this->sendText($conversation, 'busco zapatillas');

        $this->assertDatabaseMissing('helpdesk_conversation_items', [
            'conversation_id' => $conversation->id,
            'type' => 'product_carousel',
        ], 'helpdesk');
    }

    public function test_no_carousel_when_no_feed_configured(): void
    {
        $web = WebFactory::new()->create([
            'enable_product_search' => true,
            'product_feed_url' => null,
        ]);
        $conversation = $this->makeConversation($web);

        $this->sendText($conversation, 'busco zapatillas');

        $this->assertDatabaseMissing('helpdesk_conversation_items', [
            'conversation_id' => $conversation->id,
            'type' => 'product_carousel',
        ], 'helpdesk');
    }
}
