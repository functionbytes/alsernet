<?php

namespace Modules\Mailrelay\Tests\Unit;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Mockery;
use Modules\Mailrelay\Contracts\MailProviderInterface;
use Modules\Mailrelay\Entities\Campaign;
use Modules\Mailrelay\Entities\CampaignAnalytics;
use Modules\Mailrelay\Enums\CampaignStatus;
use Modules\Mailrelay\Jobs\SyncCampaignStatsJob;
use Modules\Mailrelay\Services\ProviderManager;
use Tests\TestCase;

class SyncCampaignStatsJobTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function job_is_queued_and_has_correct_config(): void
    {
        $job = new SyncCampaignStatsJob;

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(300, $job->timeout);
        $this->assertTrue($job->deleteWhenMissingModels);
    }

    /** @test */
    public function job_implements_should_queue(): void
    {
        $job = new SyncCampaignStatsJob;

        $this->assertInstanceOf(ShouldQueue::class, $job);
    }

    /** @test */
    public function handle_logs_info_when_no_campaigns_need_syncing(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('SyncCampaignStats: no campaigns need syncing');

        // Mock Campaign::query() to return empty collection
        $builderMock = Mockery::mock('Illuminate\Database\Eloquent\Builder');
        $builderMock->shouldReceive('where')->with('status', CampaignStatus::SENT)->andReturnSelf();
        $builderMock->shouldReceive('whereNotNull')->with('provider_campaign_id')->andReturnSelf();
        $builderMock->shouldReceive('where')->with(Mockery::type('Closure'))->andReturnSelf();
        $builderMock->shouldReceive('get')->andReturn(collect([]));

        // Replace Campaign model
        $this->mock(Campaign::class, function ($mock) use ($builderMock) {
            $mock->shouldReceive('query')->andReturn($builderMock);
        });

        $providerManager = Mockery::mock(ProviderManager::class);
        // byId should NOT be called when there are no campaigns
        $providerManager->shouldNotReceive('byId');

        $job = new SyncCampaignStatsJob;
        $job->handle($providerManager);
    }

    /** @test */
    public function handle_syncs_campaigns_and_updates_analytics(): void
    {
        $analytics = Mockery::mock(CampaignAnalytics::class);
        $analytics->shouldReceive('updateFromMailrelay')
            ->once()
            ->with(Mockery::on(function ($stats) {
                return $stats['sent_count'] === 500
                    && $stats['opened_count'] === 250
                    && $stats['clicked_count'] === 100;
            }));

        $campaign = Mockery::mock(Campaign::class)->makePartial();
        $campaign->id = 1;
        $campaign->mail_provider_id = 10;
        $campaign->provider_campaign_id = 'ext-campaign-001';
        $campaign->shouldReceive('getAttribute')->with('analytics')->andReturn($analytics);
        $campaign->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $campaign->shouldReceive('getAttribute')->with('mail_provider_id')->andReturn(10);
        $campaign->shouldReceive('getAttribute')->with('provider_campaign_id')->andReturn('ext-campaign-001');

        $collection = collect([$campaign]);

        $builderMock = Mockery::mock('Illuminate\Database\Eloquent\Builder');
        $builderMock->shouldReceive('where')->with('status', CampaignStatus::SENT)->andReturnSelf();
        $builderMock->shouldReceive('whereNotNull')->with('provider_campaign_id')->andReturnSelf();
        $builderMock->shouldReceive('where')->with(Mockery::type('Closure'))->andReturnSelf();
        $builderMock->shouldReceive('get')->andReturn($collection);

        $this->mock(Campaign::class, function ($mock) use ($builderMock) {
            $mock->shouldReceive('query')->andReturn($builderMock);
        });

        $mockProvider = Mockery::mock(MailProviderInterface::class);
        $mockProvider->shouldReceive('getStats')
            ->once()
            ->with('ext-campaign-001')
            ->andReturn([
                'sent_count' => 500,
                'opened_count' => 250,
                'clicked_count' => 100,
                'bounced_count' => 5,
                'unsubscribed_count' => 2,
            ]);

        $providerManager = Mockery::mock(ProviderManager::class);
        $providerManager->shouldReceive('byId')
            ->once()
            ->with(10)
            ->andReturn($mockProvider);

        Log::shouldReceive('info')->atLeast()->once();
        Log::shouldReceive('debug')->atLeast()->once();

        $job = new SyncCampaignStatsJob;
        $job->handle($providerManager);
    }

    /** @test */
    public function handle_continues_on_individual_campaign_failure(): void
    {
        $failingCampaign = Mockery::mock(Campaign::class)->makePartial();
        $failingCampaign->id = 1;
        $failingCampaign->mail_provider_id = 10;
        $failingCampaign->provider_campaign_id = 'ext-fail';
        $failingCampaign->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $failingCampaign->shouldReceive('getAttribute')->with('mail_provider_id')->andReturn(10);
        $failingCampaign->shouldReceive('getAttribute')->with('provider_campaign_id')->andReturn('ext-fail');

        $successAnalytics = Mockery::mock(CampaignAnalytics::class);
        $successAnalytics->shouldReceive('updateFromMailrelay')->once();

        $successCampaign = Mockery::mock(Campaign::class)->makePartial();
        $successCampaign->id = 2;
        $successCampaign->mail_provider_id = 10;
        $successCampaign->provider_campaign_id = 'ext-ok';
        $successCampaign->shouldReceive('getAttribute')->with('analytics')->andReturn($successAnalytics);
        $successCampaign->shouldReceive('getAttribute')->with('id')->andReturn(2);
        $successCampaign->shouldReceive('getAttribute')->with('mail_provider_id')->andReturn(10);
        $successCampaign->shouldReceive('getAttribute')->with('provider_campaign_id')->andReturn('ext-ok');

        $collection = collect([$failingCampaign, $successCampaign]);

        $builderMock = Mockery::mock('Illuminate\Database\Eloquent\Builder');
        $builderMock->shouldReceive('where')->andReturnSelf();
        $builderMock->shouldReceive('whereNotNull')->andReturnSelf();
        $builderMock->shouldReceive('get')->andReturn($collection);

        $this->mock(Campaign::class, function ($mock) use ($builderMock) {
            $mock->shouldReceive('query')->andReturn($builderMock);
        });

        $mockProvider = Mockery::mock(MailProviderInterface::class);
        $mockProvider->shouldReceive('getStats')
            ->with('ext-fail')
            ->andThrow(new \Exception('API unavailable'));
        $mockProvider->shouldReceive('getStats')
            ->with('ext-ok')
            ->andReturn(['sent_count' => 100, 'opened_count' => 50, 'clicked_count' => 25]);

        $providerManager = Mockery::mock(ProviderManager::class);
        $providerManager->shouldReceive('byId')
            ->with(10)
            ->andReturn($mockProvider);

        Log::shouldReceive('info')->atLeast()->once();
        Log::shouldReceive('debug')->atLeast()->once();
        Log::shouldReceive('error')->once();

        $job = new SyncCampaignStatsJob;
        $job->handle($providerManager);
    }

    /** @test */
    public function failed_method_logs_critical_message(): void
    {
        Log::shouldReceive('critical')
            ->once()
            ->with('SyncCampaignStats: job failed permanently', Mockery::on(function ($context) {
                return isset($context['error']) && $context['error'] === 'Fatal error';
            }));

        $job = new SyncCampaignStatsJob;
        $job->failed(new \RuntimeException('Fatal error'));
    }
}
