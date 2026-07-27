<?php

namespace Modules\Campaign\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Campaign\Jobs\DispatchCampaignWebhook;
use Modules\Campaign\Models\Campaign;
use Modules\Campaign\Models\CampaignWebhook;
use Tests\TestCase;

/**
 * El campaign_id/url del webhook lo controla quien tenga acceso a la API de
 * Campaign — sin este guard, un webhook podia apuntar a IPs internas o al
 * endpoint de metadata de la nube (169.254.169.254).
 */
class WebhookSsrfGuardTest extends TestCase
{
    use RefreshDatabase;

    private function makeWebhook(string $url): CampaignWebhook
    {
        $campaign = Campaign::forceCreate([
            'name' => 'C', 'subject' => 'S', 'from_email' => 'a@a.com', 'from_name' => 'A', 'reply_to' => 'a@a.com',
        ]);

        return CampaignWebhook::forceCreate([
            'campaign_id' => $campaign->id,
            'name' => 'wh',
            'event' => 'sent',
            'url' => $url,
            'method' => 'POST',
            'enabled' => true,
        ]);
    }

    public function test_blocks_webhook_pointing_to_cloud_metadata_endpoint(): void
    {
        Http::fake();
        Log::spy();

        $webhook = $this->makeWebhook('http://169.254.169.254/latest/meta-data/');

        (new DispatchCampaignWebhook($webhook->id, ['event' => 'sent']))->handle();

        Http::assertNothingSent();
        Log::shouldHaveReceived('warning')->once()->withArgs(
            fn (string $message) => str_contains($message, 'SSRF guard')
        );
    }

    public function test_blocks_webhook_pointing_to_loopback(): void
    {
        Http::fake();

        $webhook = $this->makeWebhook('http://127.0.0.1:9000/internal-admin');

        (new DispatchCampaignWebhook($webhook->id, ['event' => 'sent']))->handle();

        Http::assertNothingSent();
    }

    public function test_allows_public_https_webhook(): void
    {
        Http::fake();

        $webhook = $this->makeWebhook('https://example.com/webhook');

        (new DispatchCampaignWebhook($webhook->id, ['event' => 'sent']))->handle();

        Http::assertSent(fn ($req) => $req->url() === 'https://example.com/webhook');
    }
}
