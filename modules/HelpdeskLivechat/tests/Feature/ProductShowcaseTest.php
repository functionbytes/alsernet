<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskLivechat\Database\Factories\WebFactory;
use Modules\HelpdeskLivechat\Services\Catalog\CatalogProduct;
use Modules\HelpdeskLivechat\Services\Widget\ProductShowcaseService;
use Modules\HelpdeskLivechat\Tests\Concerns\SeedsOpenConversationStatus;
use Tests\TestCase;

/**
 * El "coviewer" en el hilo: publicar un carrusel de productos como mensaje de
 * la conversación y emitirlo en tiempo real.
 */
class ProductShowcaseTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsOpenConversationStatus;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedOpenConversationStatus();
    }

    private function makeConversation(): Conversation
    {
        $web = WebFactory::new()->create();
        $customer = Customer::factory()->create();
        $inbox = Inbox::firstOrCreate(
            ['channel_type' => 'web', 'channel_id' => $web->id],
            ['uid' => (string) Str::uuid(), 'name' => 'Showcase Inbox', 'is_active' => true]
        );

        return Conversation::factory()->create([
            'customer_id' => $customer->id,
            'inbox_id' => $inbox->id,
            'channel' => 'web',
        ]);
    }

    private function products(): array
    {
        return [
            new CatalogProduct(id: '1', title: 'Camiseta', imageUrl: 'https://x/1.jpg', price: 20.0, currency: 'EUR'),
            new CatalogProduct(id: '2', title: 'Pantalón', imageUrl: 'https://x/2.jpg', price: 40.0, currency: 'EUR'),
        ];
    }

    public function test_showcase_creates_product_carousel_item_and_broadcasts(): void
    {
        Event::fake([ConversationMessageCreated::class]);

        $conversation = $this->makeConversation();

        /** @var ProductShowcaseService $service */
        $service = app(ProductShowcaseService::class);
        $item = $service->showcase($conversation, $this->products(), userId: 7, note: 'Mira esto');

        $this->assertNotNull($item);
        $this->assertSame('product_carousel', $item->type);
        $this->assertSame(7, $item->user_id);
        $this->assertCount(2, $item->metadata['products']);
        $this->assertSame('Mira esto', $item->body);

        $this->assertDatabaseHas('helpdesk_conversation_items', [
            'id' => $item->id,
            'type' => 'product_carousel',
        ], 'helpdesk');

        Event::assertDispatched(ConversationMessageCreated::class);
    }

    public function test_showcase_with_no_products_returns_null_and_creates_nothing(): void
    {
        Event::fake([ConversationMessageCreated::class]);

        $conversation = $this->makeConversation();

        $service = app(ProductShowcaseService::class);
        $item = $service->showcase($conversation, [], userId: 7);

        $this->assertNull($item);
        Event::assertNotDispatched(ConversationMessageCreated::class);
    }

    public function test_showcase_caps_products_at_max(): void
    {
        $conversation = $this->makeConversation();

        $many = [];
        for ($i = 1; $i <= ProductShowcaseService::MAX_PRODUCTS + 5; $i++) {
            $many[] = new CatalogProduct(id: (string) $i, title: 'P'.$i);
        }

        $service = app(ProductShowcaseService::class);
        $item = $service->showcase($conversation, $many);

        $this->assertCount(ProductShowcaseService::MAX_PRODUCTS, $item->metadata['products']);
    }
}
