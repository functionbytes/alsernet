<?php

namespace Modules\Campaign\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\CampaignSendingServers\Events\BounceDetected;
use Modules\CampaignSendingServers\Events\FeedbackLoopDetected;
use Modules\CampaignSendingServers\Models\SendingServer;
use Tests\TestCase;

/**
 * Los webhooks entrantes de proveedores (bounce/queja) exigen que el {serverUid}
 * de la URL corresponda a un SendingServer existente. El UUID no viaja en el
 * email, así que actúa como secreto compartido: sin él, un atacante que conozca
 * un message_id NO puede forjar bounces/quejas que corrompan el tracking ajeno.
 */
class ProviderWebhookAuthTest extends TestCase
{
    use DatabaseTransactions;

    private function makeServer(string $type = 'sendgrid'): SendingServer
    {
        return SendingServer::query()->create([
            'uid' => (string) Str::uuid(),
            'name' => 'Test '.$type,
            'type' => $type,
        ]);
    }

    public function test_sendgrid_webhook_rejects_unknown_server_uid(): void
    {
        Event::fake([BounceDetected::class, FeedbackLoopDetected::class]);

        $this->postJson(route('campaign.webhooks.sendgrid', (string) Str::uuid()), [
            ['event' => 'bounce', 'sg_message_id' => 'msg-1', 'email' => 'x@example.com'],
        ])->assertNotFound();

        Event::assertNothingDispatched();
    }

    public function test_sendgrid_webhook_accepts_known_server_and_dispatches_bounce(): void
    {
        Event::fake([BounceDetected::class, FeedbackLoopDetected::class]);
        $server = $this->makeServer('sendgrid');

        $this->postJson(route('campaign.webhooks.sendgrid', $server->uid), [
            ['event' => 'bounce', 'sg_message_id' => 'msg-1', 'email' => 'x@example.com', 'reason' => '550'],
        ])->assertOk();

        Event::assertDispatched(BounceDetected::class);
    }

    public function test_mailgun_webhook_rejects_unknown_server_uid(): void
    {
        Event::fake([BounceDetected::class, FeedbackLoopDetected::class]);

        $this->postJson(route('campaign.webhooks.mailgun', (string) Str::uuid()), [
            'event-data' => ['event' => 'failed', 'message' => ['headers' => ['message-id' => 'm-1']]],
        ])->assertNotFound();

        Event::assertNothingDispatched();
    }

    public function test_postmark_webhook_rejects_unknown_server_uid(): void
    {
        Event::fake([BounceDetected::class, FeedbackLoopDetected::class]);

        $this->postJson(route('campaign.webhooks.postmark', (string) Str::uuid()), [
            'RecordType' => 'Bounce', 'MessageID' => 'm-1', 'Email' => 'x@example.com',
        ])->assertNotFound();

        Event::assertNothingDispatched();
    }
}
