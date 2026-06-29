<?php

namespace Modules\HelpdeskEmailLog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Modules\HelpdeskEmailLog\Tests\Fixtures\RedactedTestMail;
use Modules\HelpdeskEmailLog\Tests\Fixtures\TrackedTestMail;
use Tests\TestCase;

class BodyRedactionAndTruncationTest extends TestCase
{
    use RefreshDatabase;

    public function test_body_is_redacted_for_mailables_marked_as_sensitive(): void
    {
        Mail::to('user@example.test')->send(new RedactedTestMail);

        $log = EmailLog::query()->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame('Sensitive: your reset link', $log->subject);
        $this->assertNull($log->body_html);
        $this->assertNull($log->body_text);
        $this->assertTrue($log->metadata['redacted'] ?? false);
    }

    public function test_body_is_redacted_when_class_is_listed_in_config(): void
    {
        config()->set('helpdeskemaillog.redact_body_for_classes', [TrackedTestMail::class]);

        Mail::to('user@example.test')->send(new TrackedTestMail);

        $log = EmailLog::query()->latest('id')->first();

        $this->assertNull($log->body_html);
        $this->assertTrue($log->metadata['redacted'] ?? false);
    }

    public function test_body_is_redacted_when_module_is_listed_in_config(): void
    {
        config()->set('helpdeskemaillog.redact_body_for_modules', ['HelpdeskTickets']);

        Mail::to('user@example.test')->send(new TrackedTestMail);

        $log = EmailLog::query()->latest('id')->first();

        $this->assertNull($log->body_html);
        $this->assertTrue($log->metadata['redacted'] ?? false);
    }

    public function test_body_is_truncated_when_exceeding_max_bytes(): void
    {
        config()->set('helpdeskemaillog.max_body_bytes', 64);

        $log = EmailLog::factory()->create([
            'body_html' => str_repeat('A', 80),
        ]);

        // Save then re-fetch — truncation happens at write time via listeners,
        // so we exercise the helper through a fresh send here.
        Mail::raw(str_repeat('B', 300), function ($m) {
            $m->to('user@example.test')->subject('Big body');
        });

        $fresh = EmailLog::query()->latest('id')->first();

        $this->assertNotNull($fresh->body_text);
        $this->assertLessThanOrEqual(
            64 + strlen("\n<!-- [helpdeskemaillog] contenido truncado -->"),
            strlen($fresh->body_text),
        );
        $this->assertStringContainsString('contenido truncado', $fresh->body_text);
        $this->assertNotNull($log->refresh()); // sanity: factory created row still exists
    }
}
