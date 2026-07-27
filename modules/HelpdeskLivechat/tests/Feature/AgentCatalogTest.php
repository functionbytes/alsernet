<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
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
 * API de catálogo para el agente: buscar y compartir productos en la
 * conversación (coviewer) desde el panel, autenticado y con permiso.
 */
class AgentCatalogTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsHelpdeskRoles;
    use SeedsOpenConversationStatus;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private const FEED_URL = 'https://shop.example/feed.json';

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionsSeeder::class);
        $this->seedHelpdeskRoles();
        $this->seedOpenConversationStatus();

        $this->agent = User::factory()->create();
        $this->agent->givePermissionTo('helpdesk.conversations.reply');

        Http::fake([self::FEED_URL => Http::response([
            ['id' => '1', 'title' => 'Zapatillas running', 'price' => 90],
            ['id' => '2', 'title' => 'Botas montaña', 'price' => 120],
        ], 200)]);
    }

    private function makeConversation(): Conversation
    {
        $web = WebFactory::new()->create(['product_feed_url' => self::FEED_URL]);
        $customer = Customer::factory()->create();
        $inbox = Inbox::firstOrCreate(
            ['channel_type' => 'web', 'channel_id' => $web->id],
            ['uid' => (string) Str::uuid(), 'name' => 'Agent Inbox', 'is_active' => true]
        );

        return Conversation::factory()->create([
            'customer_id' => $customer->id,
            'inbox_id' => $inbox->id,
            'channel' => 'web',
        ]);
    }

    public function test_guest_cannot_search_catalog(): void
    {
        $conversation = $this->makeConversation();

        $this->getJson(route('helpdesk-livechat.agent.catalog.search', $conversation).'?q=zapatillas')
            ->assertUnauthorized();
    }

    public function test_agent_can_search_catalog(): void
    {
        $conversation = $this->makeConversation();

        $this->actingAs($this->agent)
            ->getJson(route('helpdesk-livechat.agent.catalog.search', $conversation).'?q=zapatillas')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.products.0.title', 'Zapatillas running');
    }

    public function test_agent_can_share_products_into_conversation(): void
    {
        $conversation = $this->makeConversation();

        $this->actingAs($this->agent)
            ->postJson(route('helpdesk-livechat.agent.catalog.share', $conversation), [
                'product_ids' => ['1', '2'],
                'note' => 'Estas dos opciones',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $carousel = ConversationItem::on('helpdesk')
            ->where('conversation_id', $conversation->id)
            ->where('type', 'product_carousel')
            ->first();

        $this->assertNotNull($carousel);
        $this->assertCount(2, $carousel->metadata['products']);
        $this->assertSame($this->agent->id, $carousel->user_id);
        $this->assertFalse((bool) ($carousel->metadata['is_bot'] ?? false));
    }

    public function test_share_with_unknown_products_returns_422(): void
    {
        $conversation = $this->makeConversation();

        $this->actingAs($this->agent)
            ->postJson(route('helpdesk-livechat.agent.catalog.share', $conversation), [
                'product_ids' => ['999'],
            ])
            ->assertStatus(422);
    }
}
