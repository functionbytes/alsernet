<?php

namespace Modules\Helpdesk\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Modules\Helpdesk\Jobs\SendBroadcastChunkJob;
use Modules\Helpdesk\Jobs\SendBroadcastJob;
use Modules\Helpdesk\Models\Campaigns\Broadcast;
use Modules\Helpdesk\Models\Campaigns\BroadcastRecipient;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\FacebookMessengerService;
use Modules\Helpdesk\Services\InstagramService;
use Modules\Helpdesk\Services\WhatsAppBusinessService;
use Tests\TestCase;

class SendBroadcastChunkingTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = [null, 'helpdesk'];

    private function broadcastWithRecipients(int $count): Broadcast
    {
        $broadcast = new Broadcast([
            'name' => 'Campaña de prueba',
            'channel' => 'web',
            'template_type' => 'text',
            'body' => 'Hola',
            'status' => 'pending',
        ]);
        $broadcast->created_by = User::factory()->create()->id;
        $broadcast->save();

        for ($i = 0; $i < $count; $i++) {
            $customer = Customer::factory()->create();
            BroadcastRecipient::create([
                'broadcast_id' => $broadcast->id,
                'customer_id' => $customer->id,
                'status' => 'pending',
            ]);
        }

        return $broadcast;
    }

    public function test_el_dispatcher_trocea_en_chunk_jobs_y_marca_sending(): void
    {
        Queue::fake();

        $broadcast = $this->broadcastWithRecipients(3);

        (new SendBroadcastJob($broadcast))->handle();

        Queue::assertPushed(SendBroadcastChunkJob::class);
        $this->assertSame('sending', $broadcast->fresh()->status);
    }

    public function test_el_chunk_job_envia_y_finaliza_el_broadcast(): void
    {
        $broadcast = $this->broadcastWithRecipients(2);
        $ids = BroadcastRecipient::where('broadcast_id', $broadcast->id)->pluck('id')->all();

        (new SendBroadcastChunkJob($broadcast->id, $ids))->handle(
            app(WhatsAppBusinessService::class),
            app(FacebookMessengerService::class),
            app(InstagramService::class),
        );

        $fresh = $broadcast->fresh();
        $this->assertSame('sent', $fresh->status);
        $this->assertSame(2, (int) $fresh->delivered_count);
    }
}
