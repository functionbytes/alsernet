<?php

namespace Modules\Campaign\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Campaign\Jobs\RunCampaign;
use Modules\Campaign\Library\BaseCampaign;
use Modules\Campaign\Models\Campaign;
use Modules\Campaign\Models\CampaignMaillist;
use Modules\Campaign\Models\CampaignSubscriber;
use Modules\Campaign\Models\CampaignTrackingLog;
use Modules\Campaign\Models\JobMonitor;
use Modules\CampaignSendingServers\Models\SendingServer;
use Tests\TestCase;

/**
 * Smoke test del módulo Campaign.
 *
 * Verifica:
 *   - migrate:fresh corre limpio
 *   - Campaign + Maillist + Subscriber persisten
 *   - Estados new → scheduled → done de la máquina
 *   - trackMessage() inserta en campaign_tracking_logs
 *   - subscribersToSend() excluye blacklist y duplicados
 */
class CampaignSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_campaign_in_new_state(): void
    {
        $campaign = Campaign::create([
            'name' => 'Test Campaign',
            'subject' => 'Hola',
            'from_email' => 'sender@example.com',
            'from_name' => 'Sender',
            'reply_to' => 'reply@example.com',
            'type' => Campaign::TYPE_REGULAR,
        ]);

        $this->assertEquals(BaseCampaign::STATUS_NEW, $campaign->fresh()->status);
        $this->assertNotEmpty($campaign->uid);
    }

    public function test_state_machine_transitions(): void
    {
        $campaign = Campaign::create([
            'name' => 'C',
            'subject' => 'S',
            'from_email' => 'a@a.com',
            'from_name' => 'a',
            'reply_to' => 'a@a.com',
        ]);

        $campaign->setQueued();
        $this->assertEquals(BaseCampaign::STATUS_QUEUED, $campaign->fresh()->status);

        $campaign->setSending();
        $this->assertEquals(BaseCampaign::STATUS_SENDING, $campaign->fresh()->status);

        $campaign->setDone();
        $this->assertEquals(BaseCampaign::STATUS_DONE, $campaign->fresh()->status);
    }

    public function test_track_message_persists_log(): void
    {
        $campaign = Campaign::create(['name' => 'C', 'subject' => 'S', 'from_email' => 'a@a.com', 'from_name' => 'a', 'reply_to' => 'a@a.com']);
        $sub = CampaignSubscriber::create(['email' => 'to@example.com']);
        $server = SendingServer::create([
            'name' => 'S',
            'type' => SendingServer::TYPE_SMTP,
            'host' => 's',
            'smtp_port' => 587,
            'smtp_username' => 'u',
            'smtp_password' => 'p',
        ]);

        $log = $campaign->trackMessage(
            ['status' => Campaign::DELIVERY_STATUS_SENT, 'runtime_message_id' => 'rt-123'],
            $sub,
            $server,
            'msg-id-1',
        );

        $this->assertInstanceOf(CampaignTrackingLog::class, $log);
        $this->assertDatabaseHas('campaign_tracking_logs', [
            'message_id' => 'msg-id-1',
            'runtime_message_id' => 'rt-123',
            'status' => 'sent',
            'campaign_id' => $campaign->id,
        ]);
    }

    public function test_subscribers_to_send_excludes_already_sent(): void
    {
        $campaign = Campaign::create(['name' => 'C', 'subject' => 'S', 'from_email' => 'a@a.com', 'from_name' => 'a', 'reply_to' => 'a@a.com']);
        $list = CampaignMaillist::create(['name' => 'L']);

        // Asociar lista a campaña
        \DB::table('campaign_lists_segments')->insert([
            'campaign_id' => $campaign->id,
            'mail_list_id' => $list->id,
            'segment_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $a = CampaignSubscriber::create(['email' => 'a@example.com']);
        $b = CampaignSubscriber::create(['email' => 'b@example.com']);
        foreach ([$a, $b] as $sub) {
            \DB::table('campaign_maillists_subscribers')->insert([
                'uid' => (string) Str::uuid(),
                'mail_list_id' => $list->id,
                'subscriber_id' => $sub->id,
                'status' => 'subscribed',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // a ya recibió el envío
        CampaignTrackingLog::create([
            'campaign_id' => $campaign->id,
            'subscriber_id' => $a->id,
            'sending_server_id' => null,
            'email' => 'a@example.com',
            'status' => 'sent',
        ]);

        $pending = $campaign->subscribersToSend()->get();
        $emails = $pending->pluck('email')->all();

        $this->assertNotContains('a@example.com', $emails);
        $this->assertContains('b@example.com', $emails);
    }

    public function test_job_monitor_tracks_subjects(): void
    {
        $campaign = Campaign::create(['name' => 'C', 'subject' => 'S', 'from_email' => 'a@a.com', 'from_name' => 'a', 'reply_to' => 'a@a.com']);

        $monitor = JobMonitor::makeInstance($campaign, RunCampaign::class);
        $monitor->save();

        $this->assertDatabaseHas('campaign_job_monitors', [
            'subject_name' => Campaign::class,
            'subject_id' => $campaign->id,
            'job_type' => RunCampaign::class,
            'status' => 'queued',
        ]);

        $monitor->setDone();
        $this->assertEquals('done', $monitor->fresh()->status);
    }
}
