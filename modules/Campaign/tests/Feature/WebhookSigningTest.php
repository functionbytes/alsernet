<?php

namespace Modules\Campaign\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Modules\Campaign\Jobs\DispatchCampaignWebhook;
use Modules\Campaign\Library\WebhookDispatcher;
use Modules\Campaign\Models\Campaign;
use Modules\Campaign\Models\CampaignWebhook;
use Tests\TestCase;

/**
 * Verifica el HMAC-SHA256 firmado de webhooks (X-Webhook-Signature header).
 */
class WebhookSigningTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_dispatcher_emits_jobs(): void
    {
        Bus::fake();

        $campaign = Campaign::forceCreate(['name' => 'C', 'subject' => 'S', 'from_email' => 'a@a.com', 'from_name' => 'a', 'reply_to' => 'a@a.com']);
        CampaignWebhook::forceCreate([
            'campaign_id' => $campaign->id,
            'name' => 'wh',
            'event' => 'sent',
            'url' => 'https://example.com/webhook',
            'method' => 'POST',
            'enabled' => true,
        ]);

        WebhookDispatcher::emit($campaign, 'sent', ['email' => 'x@example.com']);

        Bus::assertDispatched(DispatchCampaignWebhook::class);
    }

    public function test_webhook_includes_hmac_signature_when_secret_set(): void
    {
        Http::fake();

        $campaign = Campaign::forceCreate(['name' => 'C', 'subject' => 'S', 'from_email' => 'a@a.com', 'from_name' => 'a', 'reply_to' => 'a@a.com']);
        $secret = 'super-secret-key';
        $webhook = CampaignWebhook::forceCreate([
            'campaign_id' => $campaign->id,
            'name' => 'wh',
            'event' => 'sent',
            'url' => 'https://example.com/webhook',
            'method' => 'POST',
            'enabled' => true,
            'secret' => $secret,
        ]);

        $payload = [
            'event' => 'sent',
            'campaign_uid' => $campaign->uid,
            'campaign_id' => $campaign->id,
            'timestamp' => now()->getTimestamp(),
            'email' => 'x@example.com',
        ];

        (new DispatchCampaignWebhook($webhook->id, $payload))->handle();

        Http::assertSent(function ($req) use ($secret) {
            $timestamp = $req->header('X-Webhook-Timestamp')[0];
            $body = $req->body();
            $expectedSig = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$body, $secret);

            return $req->hasHeader('X-Webhook-Signature', $expectedSig)
                && $req->hasHeader('X-Webhook-Event', 'sent');
        });
    }

    public function test_webhook_skips_when_disabled(): void
    {
        Http::fake();

        $campaign = Campaign::forceCreate(['name' => 'C', 'subject' => 'S', 'from_email' => 'a@a.com', 'from_name' => 'a', 'reply_to' => 'a@a.com']);
        $webhook = CampaignWebhook::forceCreate([
            'campaign_id' => $campaign->id,
            'name' => 'wh',
            'event' => 'sent',
            'url' => 'https://example.com/webhook',
            'method' => 'POST',
            'enabled' => false,
        ]);

        (new DispatchCampaignWebhook($webhook->id, []))->handle();

        Http::assertNothingSent();
    }
}
