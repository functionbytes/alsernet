<?php

namespace Modules\HelpdeskCampaigns\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Events\CustomerGdprDeleted;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskCampaigns\Listeners\AnonymizeCampaignImpressions;
use Modules\HelpdeskCampaigns\Models\Campaign;
use Modules\HelpdeskCampaigns\Models\CampaignImpression;
use Modules\HelpdeskCampaigns\Providers\EventServiceProvider;
use Tests\TestCase;

class AnonymizeCampaignImpressionsTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    public function test_el_borrado_gdpr_vacia_los_datos_personales_de_las_impresiones(): void
    {
        $campaign = Campaign::factory()->create();
        $customer = Customer::factory()->create();
        $other = Customer::factory()->create();

        $mine = CampaignImpression::factory()->create([
            'campaign_id' => $campaign->id,
            'customer_id' => $customer->id,
            'customer_session_id' => 'sess-abc',
            'ip_address' => '203.0.113.9',
            'metadata' => ['ua' => 'x'],
        ]);
        $theirs = CampaignImpression::factory()->create([
            'campaign_id' => $campaign->id,
            'customer_id' => $other->id,
            'customer_session_id' => 'sess-other',
            'ip_address' => '203.0.113.10',
            'metadata' => ['ua' => 'y'],
        ]);

        (new AnonymizeCampaignImpressions)->handle(new CustomerGdprDeleted(
            customer: $customer, hard: false, conversationIds: [], result: [],
        ));

        $mine->refresh();
        $this->assertNull($mine->ip_address);
        $this->assertNull($mine->customer_session_id);
        $this->assertNull($mine->metadata);
        $this->assertSame($campaign->id, $mine->campaign_id);

        $theirs->refresh();
        $this->assertSame('203.0.113.10', $theirs->ip_address);
        $this->assertSame('sess-other', $theirs->customer_session_id);
        $this->assertSame(['ua' => 'y'], $theirs->metadata);
    }

    public function test_el_listener_esta_registrado_para_el_evento_gdpr(): void
    {
        $provider = new EventServiceProvider($this->app);

        $this->assertContains(
            AnonymizeCampaignImpressions::class,
            $provider->listens()[CustomerGdprDeleted::class] ?? []
        );
    }
}
