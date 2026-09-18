<?php

namespace Modules\HelpdeskCampaigns\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Modules\HelpdeskCampaigns\Events\CampaignEnded;
use Modules\HelpdeskCampaigns\Events\CampaignPaused;
use Modules\HelpdeskCampaigns\Events\CampaignPublished;
use Modules\HelpdeskCampaigns\Events\CampaignResumed;
use Modules\HelpdeskCampaigns\Models\Campaign;
use Tests\TestCase;

/**
 * Regresión: los métodos de ciclo de vida del modelo (usados por el panel) no
 * disparaban los eventos — solo el API controller y el scheduler lo hacían. Así,
 * publicar/pausar/reanudar/finalizar desde el panel no registraba actividad, ni
 * notificaba, ni disparaba webhooks. Ahora el dispatch vive en el modelo.
 */
class CampaignLifecycleDispatchTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    public function test_publish_dispatches_event(): void
    {
        Event::fake([CampaignPublished::class]);
        $campaign = Campaign::factory()->draft()->create();

        $campaign->publish();

        Event::assertDispatched(CampaignPublished::class, fn (CampaignPublished $e): bool => $e->campaign->is($campaign));
    }

    public function test_pause_dispatches_event_when_active(): void
    {
        Event::fake([CampaignPaused::class]);
        $campaign = Campaign::factory()->active()->create(['ends_at' => null]);

        $campaign->pause();

        Event::assertDispatched(CampaignPaused::class, fn (CampaignPaused $e): bool => $e->campaign->is($campaign));
    }

    public function test_pause_does_not_dispatch_when_not_active(): void
    {
        Event::fake([CampaignPaused::class]);
        $campaign = Campaign::factory()->draft()->create();

        $campaign->pause();

        Event::assertNotDispatched(CampaignPaused::class);
    }

    public function test_resume_dispatches_event_when_paused(): void
    {
        Event::fake([CampaignResumed::class]);
        $campaign = Campaign::factory()->create(['status' => 'paused', 'published_at' => now()->subDay()]);

        $campaign->resume();

        Event::assertDispatched(CampaignResumed::class, fn (CampaignResumed $e): bool => $e->campaign->is($campaign));
    }

    public function test_end_dispatches_event(): void
    {
        Event::fake([CampaignEnded::class]);
        $campaign = Campaign::factory()->active()->create(['ends_at' => null]);

        $campaign->end();

        Event::assertDispatched(CampaignEnded::class, fn (CampaignEnded $e): bool => $e->campaign->is($campaign));
    }
}
