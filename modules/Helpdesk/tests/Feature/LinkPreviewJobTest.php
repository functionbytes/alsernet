<?php

namespace Modules\Helpdesk\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Modules\Helpdesk\Jobs\GenerateLinkPreviewJob;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\LinkPreviewService;
use Tests\TestCase;

class LinkPreviewJobTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = [null, 'helpdesk'];

    private function agentItem(string $body): ConversationItem
    {
        $customer = Customer::factory()->create();
        $status = ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1],
        );
        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'channel' => 'web',
            'status_id' => $status->id,
        ]);
        $agent = User::factory()->create();

        return ConversationItem::factory()->fromAgent($agent->id)->create([
            'conversation_id' => $conversation->id,
            'body' => $body,
        ]);
    }

    public function test_mensaje_del_agente_con_url_encola_el_preview(): void
    {
        Queue::fake();

        $this->agentItem('Mira este artículo https://example.com/post');

        Queue::assertPushed(GenerateLinkPreviewJob::class);
    }

    public function test_mensaje_del_agente_sin_url_no_encola_preview(): void
    {
        Queue::fake();

        $this->agentItem('Un mensaje normal sin enlaces');

        Queue::assertNotPushed(GenerateLinkPreviewJob::class);
    }

    public function test_el_job_genera_el_preview_y_lo_guarda(): void
    {
        Queue::fake(); // evita que el observer ejecute el job automáticamente
        Http::fake([
            '*' => Http::response('<html><head><meta property="og:title" content="Título de prueba"></head></html>', 200),
        ]);

        $item = $this->agentItem('https://example.com');

        (new GenerateLinkPreviewJob($item->id))->handle(app(LinkPreviewService::class));

        $this->assertArrayHasKey('link_preview', $item->fresh()->metadata ?? []);
    }
}
