<?php

namespace Modules\Campaign\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Campaign\Jobs\DispatchCampaignWebhook;
use Modules\Campaign\Models\Campaign;
use Modules\Campaign\Models\CampaignWebhook;
use Tests\TestCase;

class WebhookTimestampTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_includes_timestamp_header(): void
    {
        Http::fake();

        $campaign = Campaign::forceCreate(['name' => 'C', 'subject' => 'S', 'from_email' => 'a@a.com', 'from_name' => 'A', 'reply_to' => 'a@a.com']);
        $webhook = CampaignWebhook::forceCreate([
            'campaign_id' => $campaign->id,
            'name' => 'wh',
            'event' => 'sent',
            'url' => 'https://example.com/webhook',
            'method' => 'POST',
            'enabled' => true,
        ]);

        (new DispatchCampaignWebhook($webhook->id, ['event' => 'sent']))->handle();

        Http::assertSent(function ($req) {
            return $req->hasHeader('X-Webhook-Timestamp')
                && is_numeric($req->header('X-Webhook-Timestamp')[0]);
        });
    }

    public function test_webhook_signature_includes_timestamp(): void
    {
        Http::fake();

        $campaign = Campaign::forceCreate(['name' => 'C', 'subject' => 'S', 'from_email' => 'a@a.com', 'from_name' => 'A', 'reply_to' => 'a@a.com']);
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

        (new DispatchCampaignWebhook($webhook->id, ['event' => 'sent']))->handle();

        Http::assertSent(function ($req) use ($secret) {
            $timestamp = $req->header('X-Webhook-Timestamp')[0];
            $body = $req->body();
            $expectedSig = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$body, $secret);

            return $req->hasHeader('X-Webhook-Signature', $expectedSig);
        });
    }
}
