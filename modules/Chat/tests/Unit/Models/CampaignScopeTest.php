<?php

namespace Modules\Chat\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Campaigns\Campaign;
use Tests\TestCase;

class CampaignScopeTest extends TestCase
{
    use RefreshDatabase;

    private Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->account = Account::create([
            'name' => 'Test Account',
            'default_locale' => 'en',
        ]);
    }

    public function test_scope_by_status(): void
    {
        $activeCampaign = Campaign::create([
            'account_id' => $this->account->id,
            'name' => 'Active Campaign',
            'type' => 'popup',
            'status' => 'active',
        ]);

        $draftCampaign = Campaign::create([
            'account_id' => $this->account->id,
            'name' => 'Draft Campaign',
            'type' => 'banner',
            'status' => 'draft',
        ]);

        $endedCampaign = Campaign::create([
            'account_id' => $this->account->id,
            'name' => 'Ended Campaign',
            'type' => 'popup',
            'status' => 'ended',
        ]);

        $activeResults = Campaign::byStatus('active')->get();
        $this->assertCount(1, $activeResults);
        $this->assertTrue($activeResults->contains($activeCampaign));

        $draftResults = Campaign::byStatus('draft')->get();
        $this->assertCount(1, $draftResults);
        $this->assertTrue($draftResults->contains($draftCampaign));

        $endedResults = Campaign::byStatus('ended')->get();
        $this->assertCount(1, $endedResults);
        $this->assertTrue($endedResults->contains($endedCampaign));
    }

    public function test_scope_by_type(): void
    {
        $popupCampaign = Campaign::create([
            'account_id' => $this->account->id,
            'name' => 'Popup Campaign',
            'type' => 'popup',
            'status' => 'active',
        ]);

        $bannerCampaign = Campaign::create([
            'account_id' => $this->account->id,
            'name' => 'Banner Campaign',
            'type' => 'banner',
            'status' => 'active',
        ]);

        $slideInCampaign = Campaign::create([
            'account_id' => $this->account->id,
            'name' => 'Slide-in Campaign',
            'type' => 'slide-in',
            'status' => 'active',
        ]);

        $popupResults = Campaign::byType('popup')->get();
        $this->assertCount(1, $popupResults);
        $this->assertTrue($popupResults->contains($popupCampaign));

        $bannerResults = Campaign::byType('banner')->get();
        $this->assertCount(1, $bannerResults);
        $this->assertTrue($bannerResults->contains($bannerCampaign));

        $slideInResults = Campaign::byType('slide-in')->get();
        $this->assertCount(1, $slideInResults);
        $this->assertTrue($slideInResults->contains($slideInCampaign));
    }

    public function test_scope_search(): void
    {
        $campaign1 = Campaign::create([
            'account_id' => $this->account->id,
            'name' => 'Summer Sale Campaign',
            'description' => 'Big discounts for summer',
            'type' => 'popup',
            'status' => 'active',
        ]);

        $campaign2 = Campaign::create([
            'account_id' => $this->account->id,
            'name' => 'Winter Promotion',
            'description' => 'Special winter deals',
            'type' => 'banner',
            'status' => 'active',
        ]);

        $campaign3 = Campaign::create([
            'account_id' => $this->account->id,
            'name' => 'Spring Newsletter',
            'description' => 'Newsletter signup',
            'type' => 'popup',
            'status' => 'draft',
        ]);

        $nameResults = Campaign::search('Summer')->get();
        $this->assertCount(1, $nameResults);
        $this->assertTrue($nameResults->contains($campaign1));

        $descriptionResults = Campaign::search('winter deals')->get();
        $this->assertCount(1, $descriptionResults);
        $this->assertTrue($descriptionResults->contains($campaign2));

        $partialResults = Campaign::search('news')->get();
        $this->assertCount(1, $partialResults);
        $this->assertTrue($partialResults->contains($campaign3));

        $noResults = Campaign::search('autumn')->get();
        $this->assertCount(0, $noResults);
    }

    public function test_scope_active(): void
    {
        $activeCampaign = Campaign::create([
            'account_id' => $this->account->id,
            'name' => 'Active Campaign',
            'type' => 'popup',
            'status' => 'active',
        ]);

        $draftCampaign = Campaign::create([
            'account_id' => $this->account->id,
            'name' => 'Draft Campaign',
            'type' => 'banner',
            'status' => 'draft',
        ]);

        $results = Campaign::active()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($activeCampaign));
        $this->assertFalse($results->contains($draftCampaign));
    }

    public function test_scope_draft(): void
    {
        $draftCampaign = Campaign::create([
            'account_id' => $this->account->id,
            'name' => 'Draft Campaign',
            'type' => 'popup',
            'status' => 'draft',
        ]);

        $activeCampaign = Campaign::create([
            'account_id' => $this->account->id,
            'name' => 'Active Campaign',
            'type' => 'banner',
            'status' => 'active',
        ]);

        $results = Campaign::draft()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($draftCampaign));
        $this->assertFalse($results->contains($activeCampaign));
    }

    public function test_scope_scheduled(): void
    {
        $scheduledCampaign = Campaign::create([
            'account_id' => $this->account->id,
            'name' => 'Scheduled Campaign',
            'type' => 'popup',
            'status' => 'scheduled',
            'published_at' => now()->addDays(5),
        ]);

        $activeCampaign = Campaign::create([
            'account_id' => $this->account->id,
            'name' => 'Active Campaign',
            'type' => 'banner',
            'status' => 'active',
            'published_at' => now()->subDays(1),
        ]);

        $results = Campaign::scheduled()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($scheduledCampaign));
        $this->assertFalse($results->contains($activeCampaign));
    }

    public function test_scope_published(): void
    {
        $publishedActive = Campaign::create([
            'account_id' => $this->account->id,
            'name' => 'Published Active',
            'type' => 'popup',
            'status' => 'active',
            'published_at' => now()->subDays(5),
        ]);

        $publishedEnded = Campaign::create([
            'account_id' => $this->account->id,
            'name' => 'Published Ended',
            'type' => 'banner',
            'status' => 'ended',
            'published_at' => now()->subDays(10),
        ]);

        $scheduledCampaign = Campaign::create([
            'account_id' => $this->account->id,
            'name' => 'Scheduled Campaign',
            'type' => 'popup',
            'status' => 'scheduled',
            'published_at' => now()->addDays(5),
        ]);

        $results = Campaign::published()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->contains($publishedActive));
        $this->assertTrue($results->contains($publishedEnded));
        $this->assertFalse($results->contains($scheduledCampaign));
    }
}
