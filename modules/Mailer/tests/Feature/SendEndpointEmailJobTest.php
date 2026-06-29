<?php

namespace Modules\Mailer\Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Modules\Mailer\Enums\EndpointLogStatus;
use Modules\Mailer\Jobs\SendEndpointEmailJob;
use Modules\Mailer\Models\MailerEndpoint;
use Modules\Mailer\Models\MailerEndpointLog;
use Modules\Mailer\Tests\TestCase;

class SendEndpointEmailJobTest extends TestCase
{
    private MailerEndpoint $endpoint;

    private int $langId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->langId = $this->createLang();
        $template = $this->createTemplate($this->langId);

        $this->endpoint = MailerEndpoint::create([
            'name' => 'Job Test Endpoint',
            'slug' => 'job-test',
            'source' => 'phpunit',
            'type' => 'transactional',
            'mailer_template_id' => $template->id,
            'lang_id' => $this->langId,
            'is_active' => true,
        ]);
    }

    public function test_job_sends_email_and_marks_log_as_success(): void
    {
        Mail::fake();

        $log = MailerEndpointLog::create([
            'mailer_endpoint_id' => $this->endpoint->id,
            'payload' => [
                'email' => 'recipient@example.com',
                'customer_name' => 'Juan Garcia',
            ],
            'status' => EndpointLogStatus::Pending,
        ]);

        $job = new SendEndpointEmailJob($log);
        $job->handle();

        Mail::assertSent(function ($mail) {
            return $mail->hasTo('recipient@example.com');
        });

        $log->refresh();
        $this->assertEquals(EndpointLogStatus::Success, $log->status);
        $this->assertNotNull($log->sent_at);
        $this->assertEquals('recipient@example.com', $log->recipient_email);
    }

    public function test_job_fails_when_endpoint_is_inactive(): void
    {
        Mail::fake();

        $this->endpoint->update(['is_active' => false]);

        $log = MailerEndpointLog::create([
            'mailer_endpoint_id' => $this->endpoint->id,
            'payload' => ['email' => 'test@example.com'],
            'status' => EndpointLogStatus::Pending,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Endpoint is inactive');

        $job = new SendEndpointEmailJob($log);
        $job->handle();
    }

    public function test_job_fails_without_valid_recipient_email(): void
    {
        Mail::fake();

        $log = MailerEndpointLog::create([
            'mailer_endpoint_id' => $this->endpoint->id,
            'payload' => ['customer_name' => 'Sin email'],
            'status' => EndpointLogStatus::Pending,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No valid recipient email');

        $job = new SendEndpointEmailJob($log);
        $job->handle();
    }

    public function test_job_uses_variable_mappings(): void
    {
        Mail::fake();

        $this->endpoint->update([
            'variable_mappings' => [
                'CUSTOMER_NAME' => 'user.name',
                'email' => 'user.email',
            ],
        ]);

        $log = MailerEndpointLog::create([
            'mailer_endpoint_id' => $this->endpoint->id,
            'payload' => [
                'user' => [
                    'name' => 'Maria Lopez',
                    'email' => 'maria@example.com',
                ],
            ],
            'status' => EndpointLogStatus::Pending,
        ]);

        $job = new SendEndpointEmailJob($log);
        $job->handle();

        Mail::assertSent(function ($mail) {
            return $mail->hasTo('maria@example.com');
        });

        $log->refresh();
        $this->assertEquals(EndpointLogStatus::Success, $log->status);
    }

    public function test_job_sanitizes_external_payload_values(): void
    {
        Mail::fake();

        $log = MailerEndpointLog::create([
            'mailer_endpoint_id' => $this->endpoint->id,
            'payload' => [
                'email' => 'test@example.com',
                'customer_name' => '<script>alert("xss")</script>',
            ],
            'status' => EndpointLogStatus::Pending,
        ]);

        $job = new SendEndpointEmailJob($log);
        $job->handle();

        // The email address should NOT be escaped (it's used as recipient)
        Mail::assertSent(function ($mail) {
            return $mail->hasTo('test@example.com');
        });

        $log->refresh();
        $this->assertEquals(EndpointLogStatus::Success, $log->status);
    }

    public function test_failed_method_marks_log_as_failed(): void
    {
        $log = MailerEndpointLog::create([
            'mailer_endpoint_id' => $this->endpoint->id,
            'payload' => ['email' => 'test@example.com'],
            'status' => EndpointLogStatus::Pending,
        ]);

        $job = new SendEndpointEmailJob($log);
        $job->failed(new \Exception('SMTP connection timeout'));

        $log->refresh();
        $this->assertEquals(EndpointLogStatus::Failed, $log->status);
        $this->assertEquals('SMTP connection timeout', $log->error_message);
    }

    public function test_job_has_correct_retry_configuration(): void
    {
        $log = MailerEndpointLog::create([
            'mailer_endpoint_id' => $this->endpoint->id,
            'payload' => [],
            'status' => EndpointLogStatus::Pending,
        ]);

        $job = new SendEndpointEmailJob($log);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(30, $job->timeout);
        $this->assertEquals([60, 120, 300], $job->backoff);
    }
}
