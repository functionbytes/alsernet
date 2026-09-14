<?php

namespace Modules\HelpdeskCampaigns\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Modules\HelpdeskCampaigns\Events\CampaignPublished;
use Modules\HelpdeskCampaigns\Models\Campaign;
use Tests\TestCase;

/**
 * Guard de aprobación en Campaign::publish(): la ruta manual de publicación
 * solo exige el permiso `update`, así que sin este guard un usuario podía
 * activar el envío masivo saltándose la revisión (`approval_required`) que sí
 * respeta el scheduler.
 */
class CampaignApprovalGuardTest extends TestCase
{
    use DatabaseTransactions;

    public function test_publish_throws_when_approval_pending(): void
    {
        Event::fake([CampaignPublished::class]);
        $campaign = Campaign::factory()->draft()->create([
            'approval_required' => true,
            'approved_at' => null,
        ]);

        $this->assertTrue($campaign->requiresPendingApproval());

        $this->expectException(\RuntimeException::class);

        try {
            $campaign->publish();
        } finally {
            // No debe activarse ni disparar el evento de envío.
            $this->assertSame('draft', $campaign->fresh()->status);
            Event::assertNotDispatched(CampaignPublished::class);
        }
    }

    public function test_publish_succeeds_once_approved(): void
    {
        Event::fake([CampaignPublished::class]);
        $campaign = Campaign::factory()->draft()->create([
            'approval_required' => true,
            'approved_at' => now(),
        ]);

        $this->assertFalse($campaign->requiresPendingApproval());

        $campaign->publish();

        $this->assertSame('active', $campaign->fresh()->status);
        Event::assertDispatched(CampaignPublished::class);
    }

    public function test_publish_succeeds_when_approval_not_required(): void
    {
        Event::fake([CampaignPublished::class]);
        $campaign = Campaign::factory()->draft()->create([
            'approval_required' => false,
        ]);

        $campaign->publish();

        $this->assertSame('active', $campaign->fresh()->status);
        Event::assertDispatched(CampaignPublished::class);
    }
}
