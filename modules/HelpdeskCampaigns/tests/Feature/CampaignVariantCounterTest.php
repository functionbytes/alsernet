<?php

namespace Modules\HelpdeskCampaigns\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskCampaigns\Events\CampaignImpressionRecorded;
use Modules\HelpdeskCampaigns\Listeners\UpdateCampaignImpressionCounters;
use Modules\HelpdeskCampaigns\Models\Campaign;
use Modules\HelpdeskCampaigns\Models\CampaignImpression;
use Modules\HelpdeskCampaigns\Models\CampaignVariant;
use Tests\TestCase;

/**
 * Regresión del CTR por variante A/B.
 *
 * El listener mantenía los contadores desnormalizados solo en la campaña, nunca
 * en la variante, así que CampaignVariant::getCtrAttribute() —clicks/impresiones—
 * devolvía siempre 0 y la comparación de variantes era inútil. Ahora, cuando la
 * impresión trae variant_id, el contador de la variante se sincroniza también.
 */
class CampaignVariantCounterTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    public function test_variant_counters_track_impressions_and_clicks(): void
    {
        $campaign = Campaign::factory()->create();
        $variant = CampaignVariant::factory()->create(['campaign_id' => $campaign->id]);

        $listener = new UpdateCampaignImpressionCounters;

        // Dos vistas y un clic sobre esa variante.
        foreach ([false, false, true] as $wasClick) {
            $impression = CampaignImpression::factory()->create([
                'campaign_id' => $campaign->id,
                'variant_id' => $variant->id,
            ]);

            $listener->handle(new CampaignImpressionRecorded($impression, $wasClick));
        }

        $campaign->refresh();
        $variant->refresh();

        $this->assertSame(2, $campaign->impressions_count);
        $this->assertSame(1, $campaign->clicks_count);

        $this->assertSame(2, $variant->impressions_count, 'Las impresiones deben contarse por variante.');
        $this->assertSame(1, $variant->clicks_count, 'Los clics deben contarse por variante.');

        // CTR = clicks/impressions * 100 = 1/2 * 100 = 50.0 (antes: siempre 0).
        $this->assertSame(50.0, $variant->ctr);
    }

    public function test_impression_without_variant_only_updates_campaign(): void
    {
        $campaign = Campaign::factory()->create();
        $variant = CampaignVariant::factory()->create(['campaign_id' => $campaign->id]);

        $impression = CampaignImpression::factory()->create([
            'campaign_id' => $campaign->id,
            'variant_id' => null,
        ]);

        (new UpdateCampaignImpressionCounters)->handle(new CampaignImpressionRecorded($impression, false));

        $campaign->refresh();
        $variant->refresh();

        $this->assertSame(1, $campaign->impressions_count);
        $this->assertSame(0, $variant->impressions_count, 'Una impresión sin variante no debe tocar ninguna variante.');
    }
}
