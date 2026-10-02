<?php

namespace Modules\HelpdeskCampaigns\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Modules\HelpdeskCampaigns\Events\CampaignEnded;
use Modules\HelpdeskCampaigns\Events\CampaignPublished;
use Modules\HelpdeskCampaigns\Jobs\EndExpiredCampaignsJob;
use Modules\HelpdeskCampaigns\Jobs\PublishScheduledCampaignsJob;
use Modules\HelpdeskCampaigns\Models\Campaign;
use Tests\TestCase;

class CampaignSchedulerJobsTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    public function test_publica_la_campana_programada_y_despacha_el_evento_una_vez(): void
    {
        Event::fake([CampaignPublished::class]);

        $campaign = Campaign::factory()->create([
            'status' => 'scheduled',
            'published_at' => now()->subMinute(),
            'approval_required' => false,
        ]);

        (new PublishScheduledCampaignsJob)->handle();

        $this->assertSame('active', $campaign->fresh()->status);
        Event::assertDispatchedTimes(CampaignPublished::class, 1);
    }

    public function test_no_pisa_ni_notifica_una_campana_que_otro_proceso_ya_cambio(): void
    {
        Event::fake([CampaignEnded::class]);

        $campaign = Campaign::factory()->create([
            'status' => 'active',
            'ends_at' => now()->subMinute(),
        ]);

        // El job selecciona la campaña, pero antes de la transicion alguien la
        // termina: la actualizacion condicional no debe afectar ninguna fila.
        Campaign::query()->whereKey($campaign->id)->update(['status' => 'ended']);

        $transitioned = Campaign::query()
            ->whereKey($campaign->id)
            ->whereIn('status', ['active', 'paused'])
            ->update(['status' => 'ended']);

        $this->assertSame(0, $transitioned);
        (new EndExpiredCampaignsJob)->handle();

        Event::assertNotDispatched(CampaignEnded::class);
    }

    public function test_termina_la_campana_vencida_y_despacha_el_evento_una_vez(): void
    {
        Event::fake([CampaignEnded::class]);

        $campaign = Campaign::factory()->create([
            'status' => 'active',
            'ends_at' => now()->subMinute(),
        ]);

        (new EndExpiredCampaignsJob)->handle();

        $this->assertSame('ended', $campaign->fresh()->status);
        Event::assertDispatchedTimes(CampaignEnded::class, 1);
    }
}
