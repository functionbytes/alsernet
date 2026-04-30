<?php

namespace Modules\Campaign\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Campaign\Models\Campaign;
use Modules\Campaign\Models\CampaignSubscriber;
use Modules\Campaign\Services\CampaignVariantSplitter;
use Tests\TestCase;

class CampaignVariantSplitterTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigns_variant_deterministically(): void
    {
        $splitter = new CampaignVariantSplitter;
        $sub = CampaignSubscriber::create(['email' => 'test@example.com', 'source' => 'test']);
        $campaign = Campaign::forceCreate([
            'name' => 'A',
            'subject' => 'S',
            'from_email' => 'a@a.com',
            'from_name' => 'A',
            'reply_to' => 'a@a.com',
            'variant' => 'A',
        ]);

        $variant = $splitter->assignVariant($sub, $campaign);
        $this->assertContains($variant, ['A', 'B']);

        // Determinístico: misma respuesta
        $this->assertSame($variant, $splitter->assignVariant($sub, $campaign));
    }

    public function test_child_campaign_always_returns_b(): void
    {
        $splitter = new CampaignVariantSplitter;
        $sub = CampaignSubscriber::create(['email' => 'test@example.com', 'source' => 'test']);
        $campaign = Campaign::forceCreate([
            'name' => 'B',
            'subject' => 'S',
            'from_email' => 'a@a.com',
            'from_name' => 'A',
            'reply_to' => 'a@a.com',
            'variant' => 'B',
        ]);

        $this->assertSame('B', $splitter->assignVariant($sub, $campaign));
    }
}
