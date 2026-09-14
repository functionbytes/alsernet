<?php

namespace Modules\HelpdeskCampaigns\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Modules\HelpdeskCampaigns\Events\CampaignPublished;
use Modules\HelpdeskCampaigns\Jobs\DeliverCampaignWebhookJob;
use Modules\HelpdeskCampaigns\Listeners\DispatchCampaignWebhooks;
use Modules\HelpdeskCampaigns\Models\Campaign;
use Tests\TestCase;

/**
 * Regresión: el listener entregaba todos los webhooks en línea y relanzaba la
 * excepción del primero que fallara, re-entregando a los que ya habían recibido
 * el evento en cada retry. Ahora encola un DeliverCampaignWebhookJob por
 * suscriptor y cada uno reintenta de forma aislada.
 */
class DispatchCampaignWebhooksTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    public function test_queues_one_delivery_job_per_subscribed_webhook(): void
    {
        Queue::fake();

        config()->set('helpdeskcampaigns.webhooks', [
            ['url' => 'https://example.com/hook-a', 'secret' => 's3cret'],
            ['url' => 'https://example.com/hook-b'],
            ['url' => 'https://example.com/hook-c', 'events' => ['campaign.ended']],
        ]);

        $campaign = Campaign::factory()->active()->create();

        (new DispatchCampaignWebhooks)->handle(new CampaignPublished($campaign));

        // hook-a y hook-b reciben campaign.published; hook-c solo escucha campaign.ended
        Queue::assertPushed(DeliverCampaignWebhookJob::class, 2);
    }

    public function test_skips_webhooks_with_unsafe_urls(): void
    {
        Queue::fake();

        config()->set('helpdeskcampaigns.webhooks', [
            ['url' => 'http://127.0.0.1/internal'],
            ['url' => 'http://169.254.169.254/latest/meta-data'],
        ]);

        $campaign = Campaign::factory()->active()->create();

        (new DispatchCampaignWebhooks)->handle(new CampaignPublished($campaign));

        Queue::assertNotPushed(DeliverCampaignWebhookJob::class);
    }

    public function test_does_nothing_without_configured_webhooks(): void
    {
        Queue::fake();

        config()->set('helpdeskcampaigns.webhooks', []);

        $campaign = Campaign::factory()->active()->create();

        (new DispatchCampaignWebhooks)->handle(new CampaignPublished($campaign));

        Queue::assertNotPushed(DeliverCampaignWebhookJob::class);
    }
}
