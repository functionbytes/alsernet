<?php

namespace Modules\Helpdesk\Tests\Feature\Outbound;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Modules\Helpdesk\Jobs\SendOutboundMessageJob;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Tests\TestCase;

class RetrySendEndpointTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = [null, 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        // Aísla la lógica del endpoint (limpieza de flag + re-dispatch) del cableado
        // de permisos Spatie: la autorización se ejerce en otros tests del módulo.
        Gate::before(fn () => true);
    }

    private function makeConversation(): Conversation
    {
        $customer = Customer::factory()->create();
        $status = ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1],
        );

        return Conversation::factory()->create([
            'customer_id' => $customer->id,
            'channel' => 'whatsapp',
            'external_sender_id' => '5211234567890',
            'status_id' => $status->id,
        ]);
    }

    public function test_reintento_limpia_el_flag_y_redispatcha_el_job(): void
    {
        Queue::fake();

        $agent = User::factory()->create();
        $item = ConversationItem::factory()->create([
            'conversation_id' => $this->makeConversation()->id,
            'user_id' => $agent->id,
            'type' => 'message',
            'body' => 'Hola',
            'external_id' => null,
            'metadata' => ['send_failed' => true, 'send_failed_at' => now()->toIso8601String()],
        ]);

        $this->actingAs($agent)
            ->postJson(route('manager.helpdesk.conversation-items.retry-send', $item))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertArrayNotHasKey('send_failed', $item->fresh()->metadata ?? []);
        Queue::assertPushed(SendOutboundMessageJob::class);
    }

    public function test_reintento_rechaza_item_no_marcado_como_fallido(): void
    {
        Queue::fake();

        $agent = User::factory()->create();
        $item = ConversationItem::factory()->create([
            'conversation_id' => $this->makeConversation()->id,
            'user_id' => $agent->id,
            'type' => 'message',
            'body' => 'Hola',
            'metadata' => [],
        ]);

        $this->actingAs($agent)
            ->postJson(route('manager.helpdesk.conversation-items.retry-send', $item))
            ->assertStatus(422);

        Queue::assertNotPushed(SendOutboundMessageJob::class);
    }
}
