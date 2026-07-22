<?php

namespace Modules\HelpdeskEmailLog\Tests\Feature;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Mail;
use Modules\HelpdeskEmailLog\Enums\EmailStatus;
use Modules\HelpdeskEmailLog\Listeners\LogEmailSent;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Modules\HelpdeskEmailLog\Tests\Fixtures\TrackedTestMail;
use Tests\TestCase;

class EmailTrackingTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    public function test_sent_listener_is_queued_off_the_request(): void
    {
        $listener = new LogEmailSent;

        $this->assertInstanceOf(ShouldQueue::class, $listener);
        $this->assertSame('emails', $listener->queue);
        $this->assertSame(3, $listener->tries);
        $this->assertSame(10, $listener->backoff);
    }

    public function test_sent_listener_is_registered_for_message_sent(): void
    {
        $raw = app('events')->getRawListeners()[MessageSent::class] ?? [];

        $this->assertContains(LogEmailSent::class, $raw);
    }

    public function test_sending_an_email_records_a_sent_log_entry(): void
    {
        Mail::to('person@example.test')->send(new TrackedTestMail);

        $log = EmailLog::query()->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame(EmailStatus::Sent, $log->status);
        $this->assertSame('Tracked test email', $log->subject);
        $this->assertSame(['person@example.test'], $log->to_addresses);
        $this->assertNotNull($log->sent_at);
        $this->assertNotNull($log->message_id);
    }

    public function test_tracking_context_is_captured_from_the_mailable(): void
    {
        Mail::to('person@example.test')->send(new TrackedTestMail(99));

        $log = EmailLog::query()->latest('id')->first();

        $this->assertSame('HelpdeskTickets', $log->module);
        $this->assertSame('Ticket', $log->entity_type);
        $this->assertSame(99, $log->entity_id);
        $this->assertSame(TrackedTestMail::class, $log->mailable_class);
    }

    public function test_internal_tracking_headers_do_not_leak_to_the_recipient(): void
    {
        Mail::to('person@example.test')->send(new TrackedTestMail);

        $sent = Mail::getSymfonyTransport()->messages();

        $this->assertCount(1, $sent);

        $headers = $sent->first()->getOriginalMessage()->getHeaders();

        foreach (['X-Email-Module', 'X-Entity-Type', 'X-Entity-Id', 'X-Mailable-Class'] as $header) {
            $this->assertFalse($headers->has($header), "Header {$header} should have been stripped.");
        }

        $this->assertTrue($headers->has('Message-ID'));
    }

    public function test_only_one_log_row_is_kept_per_email(): void
    {
        // Delta y no total global: robusto a filas residuales en la BD compartida.
        $before = EmailLog::query()->count();

        Mail::to('person@example.test')->send(new TrackedTestMail);

        $this->assertSame($before + 1, EmailLog::query()->count());
    }

    public function test_body_is_not_stored_when_disabled(): void
    {
        config()->set('helpdeskemaillog.store_body', false);

        Mail::to('person@example.test')->send(new TrackedTestMail);

        $log = EmailLog::query()->latest('id')->first();

        $this->assertNull($log->body_html);
        $this->assertNull($log->body_text);
    }
}
