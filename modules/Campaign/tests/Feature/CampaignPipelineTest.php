<?php

namespace Modules\Campaign\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Modules\Campaign\Jobs\RunCampaign;
use Modules\Campaign\Jobs\SendMessage;
use Modules\Campaign\Models\Campaign;
use Modules\Campaign\Models\CampaignMaillist;
use Modules\Campaign\Models\Subscriber;
use Modules\CampaignSendingServers\Models\Blacklist;
use Modules\CampaignSendingServers\Models\SendingServer;
use Tests\TestCase;

/**
 * Tests del pipeline completo de envío de campañas.
 */
class CampaignPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_campaign_execute_dispatches_run_campaign_job(): void
    {
        Bus::fake([RunCampaign::class]);

        $campaign = Campaign::forceCreate([
            'name' => 'Test',
            'subject' => 'S',
            'from_email' => 'a@a.com',
            'from_name' => 'A',
            'reply_to' => 'a@a.com',
        ]);

        $campaign->execute();

        Bus::assertDispatched(RunCampaign::class, fn ($job) => $job->campaign->id === $campaign->id);
        $this->assertEquals('queuing', $campaign->fresh()->status);
    }

    public function test_load_campaign_creates_send_message_jobs(): void
    {
        Queue::fake();

        $campaign = Campaign::forceCreate(['name' => 'T', 'subject' => 'S', 'from_email' => 'a@a.com', 'from_name' => 'A', 'reply_to' => 'a@a.com']);
        $list = CampaignMaillist::create(['name' => 'L']);
        $server = SendingServer::create(['name' => 'S', 'type' => SendingServer::TYPE_SMTP, 'host' => 'h', 'smtp_port' => 587, 'smtp_username' => 'u', 'smtp_password' => 'p']);

        \DB::table('campaign_lists_segments')->insert(['campaign_id' => $campaign->id, 'mail_list_id' => $list->id, 'segment_id' => null, 'created_at' => now(), 'updated_at' => now()]);
        \DB::table('campaign_maillists_sending_servers')->insert(['mail_list_id' => $list->id, 'sending_server_id' => $server->id, 'priority' => 1, 'created_at' => now(), 'updated_at' => now()]);

        $sub = Subscriber::create(['email' => 'to@example.com']);
        \DB::table('campaign_maillists_subscribers')->insert([
            'uid' => (string) Str::uuid(),
            'mail_list_id' => $list->id,
            'subscriber_id' => $sub->id,
            'status' => 'subscribed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $campaign->loadDeliveryJobs(function ($job) {
            Queue::push($job);
        }, 10);

        Queue::assertPushed(SendMessage::class);
    }

    public function test_send_message_tracks_failed_status_on_exception(): void
    {
        $campaign = Campaign::forceCreate(['name' => 'T', 'subject' => 'S', 'from_email' => 'a@a.com', 'from_name' => 'A', 'reply_to' => 'a@a.com']);
        $sub = Subscriber::create(['email' => 'to@example.com']);
        $server = SendingServer::create(['name' => 'S', 'type' => SendingServer::TYPE_SMTP, 'host' => 'h', 'smtp_port' => 587, 'smtp_username' => 'u', 'smtp_password' => 'p']);

        $job = new SendMessage($campaign, $sub, $server);
        $job->setStopOnError(false);

        $job->send();

        $this->assertDatabaseHas('campaign_tracking_logs', [
            'campaign_id' => $campaign->id,
            'email' => 'to@example.com',
            'status' => 'failed',
        ]);
    }

    public function test_subscribers_to_send_excludes_blacklisted_emails(): void
    {
        $campaign = Campaign::forceCreate(['name' => 'T', 'subject' => 'S', 'from_email' => 'a@a.com', 'from_name' => 'A', 'reply_to' => 'a@a.com']);
        $list = CampaignMaillist::create(['name' => 'L']);

        \DB::table('campaign_lists_segments')->insert(['campaign_id' => $campaign->id, 'mail_list_id' => $list->id, 'segment_id' => null, 'created_at' => now(), 'updated_at' => now()]);

        $sub = Subscriber::create(['email' => 'blacklisted@example.com']);
        \DB::table('campaign_maillists_subscribers')->insert([
            'uid' => (string) Str::uuid(),
            'mail_list_id' => $list->id,
            'subscriber_id' => $sub->id,
            'status' => 'subscribed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Blacklist::create(['email' => 'blacklisted@example.com', 'reason' => 'test']);

        $pending = $campaign->subscribersToSend()->pluck('email')->all();

        $this->assertNotContains('blacklisted@example.com', $pending);
    }
}
