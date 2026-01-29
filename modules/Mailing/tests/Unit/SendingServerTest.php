<?php

namespace Modules\Mailing\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Mailing\Models\SendingServer;
use Tests\TestCase;

class SendingServerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_sending_server(): void
    {
        $server = SendingServer::factory()->create([
            'name' => 'Test SMTP Server',
            'type' => 'smtp',
        ]);

        $this->assertDatabaseHas('mailing_sending_servers', [
            'name' => 'Test SMTP Server',
            'type' => 'smtp',
        ]);

        $this->assertEquals('Test SMTP Server', $server->name);
    }

    public function test_sending_server_has_uid(): void
    {
        $server = SendingServer::factory()->create();

        $this->assertNotNull($server->uid);
        $this->assertIsString($server->uid);
    }

    public function test_can_send_when_active_and_quota_not_exceeded(): void
    {
        $server = SendingServer::factory()->create([
            'status' => 'active',
            'quota_value' => 1000,
            'emailing_sent_today' => 500,
        ]);

        $this->assertTrue($server->canSend());
    }

    public function test_cannot_send_when_inactive(): void
    {
        $server = SendingServer::factory()->inactive()->create();

        $this->assertFalse($server->canSend());
    }

    public function test_cannot_send_when_quota_exceeded(): void
    {
        $server = SendingServer::factory()->nearQuotaLimit()->create([
            'quota_value' => 1000,
            'emailing_sent_today' => 1000,
        ]);

        $this->assertFalse($server->canSend());
    }

    public function test_quota_is_not_exceeded_when_unlimited(): void
    {
        $server = SendingServer::factory()->create([
            'quota_value' => 0,
            'emailing_sent_today' => 10000,
        ]);

        $this->assertFalse($server->isQuotaExceeded());
    }

    public function test_can_increment_sent_count(): void
    {
        $server = SendingServer::factory()->create([
            'emailing_sent_today' => 10,
            'emailing_sent_this_month' => 100,
            'total_emailing_sent' => 1000,
        ]);

        $server->incrementSentCount(5);

        $server->refresh();

        $this->assertEquals(15, $server->emailing_sent_today);
        $this->assertEquals(105, $server->emailing_sent_this_month);
        $this->assertEquals(1005, $server->total_emailing_sent);
        $this->assertNotNull($server->last_sent_at);
    }

    public function test_can_reset_daily_counters(): void
    {
        $server = SendingServer::factory()->create([
            'emailing_sent_today' => 500,
        ]);

        $server->resetDailyCounters();

        $this->assertEquals(0, $server->fresh()->emailing_sent_today);
    }

    public function test_get_type_label_returns_correct_labels(): void
    {
        $smtp = SendingServer::factory()->create(['type' => 'smtp']);
        $this->assertEquals('SMTP', $smtp->getTypeLabel());

        $sendgrid = SendingServer::factory()->sendgrid()->create();
        $this->assertEquals('SendGrid', $sendgrid->getTypeLabel());

        $mailgun = SendingServer::factory()->mailgun()->create();
        $this->assertEquals('Mailgun', $mailgun->getTypeLabel());

        $ses = SendingServer::factory()->create(['type' => 'ses']);
        $this->assertEquals('AWS SES', $ses->getTypeLabel());
    }

    public function test_get_status_label_returns_correct_labels(): void
    {
        $active = SendingServer::factory()->create(['status' => 'active']);
        $this->assertEquals('Active', $active->getStatusLabel());

        $inactive = SendingServer::factory()->inactive()->create();
        $this->assertEquals('Inactive', $inactive->getStatusLabel());

        $error = SendingServer::factory()->withError()->create();
        $this->assertEquals('Error', $error->getStatusLabel());
    }

    public function test_sending_server_encrypts_sensitive_data(): void
    {
        $server = SendingServer::factory()->create([
            'password' => 'secret_password',
            'api_key' => 'secret_api_key',
            'api_secret' => 'secret_api_secret',
        ]);

        $this->assertDatabaseMissing('mailing_sending_servers', [
            'id' => $server->id,
            'password' => 'secret_password',
        ]);
    }

    public function test_sending_server_casts_options_to_json(): void
    {
        $options = ['custom_headers' => true, 'track_opens' => true];

        $server = SendingServer::factory()->create([
            'options' => $options,
        ]);

        $this->assertIsArray($server->options);
        $this->assertEquals($options, $server->options);
    }

    public function test_sending_server_casts_boolean_fields(): void
    {
        $server = SendingServer::factory()->create([
            'allow_verify_domain' => true,
            'allow_verify_email' => false,
            'allow_custom_return_path' => true,
        ]);

        $this->assertIsBool($server->allow_verify_domain);
        $this->assertIsBool($server->allow_verify_email);
        $this->assertTrue($server->allow_verify_domain);
        $this->assertFalse($server->allow_verify_email);
    }

    public function test_sending_server_soft_deletes(): void
    {
        $server = SendingServer::factory()->create();
        $serverId = $server->id;

        $server->delete();

        $this->assertSoftDeleted('mailing_sending_servers', ['id' => $serverId]);
        $this->assertNotNull($server->fresh()?->deleted_at);
    }

    public function test_sending_server_has_bounce_handler_relationship(): void
    {
        $this->markTestSkipped('BounceHandler factory not available yet');

        $server = SendingServer::factory()->create();

        $this->assertTrue(method_exists($server, 'bounceHandler'));
    }

    public function test_sending_server_has_feedback_loop_handler_relationship(): void
    {
        $this->markTestSkipped('FeedbackLoopHandler factory not available yet');

        $server = SendingServer::factory()->create();

        $this->assertTrue(method_exists($server, 'feedbackLoopHandler'));
    }

    public function test_sending_server_has_sub_accounts_relationship(): void
    {
        $this->markTestSkipped('SubAccount factory not available yet');

        $server = SendingServer::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $server->subAccounts);
    }

    public function test_sending_server_factory_states_work_correctly(): void
    {
        $inactive = SendingServer::factory()->inactive()->create();
        $this->assertEquals('inactive', $inactive->status);

        $withError = SendingServer::factory()->error()->create();
        $this->assertEquals('error', $withError->status);
        $this->assertNotNull($withError->last_error);

        $sendgrid = SendingServer::factory()->sendgrid()->create();
        $this->assertEquals('sendgrid', $sendgrid->type);
        $this->assertNotNull($sendgrid->api_key);
        $this->assertStringStartsWith('SG.', $sendgrid->api_key);

        $mailgun = SendingServer::factory()->mailgun()->create();
        $this->assertEquals('mailgun', $mailgun->type);
        $this->assertNotNull($mailgun->api_region);

        $withActivity = SendingServer::factory()->withActivity()->create();
        $this->assertGreaterThan(0, $withActivity->emailing_sent_today);
        $this->assertNotNull($withActivity->last_sent_at);
    }

    public function test_can_create_multiple_sending_servers(): void
    {
        $servers = SendingServer::factory()->count(5)->create();

        $this->assertCount(5, $servers);
        $this->assertCount(5, SendingServer::all());
    }
}
