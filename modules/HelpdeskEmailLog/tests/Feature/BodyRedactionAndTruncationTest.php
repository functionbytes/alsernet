<?php

namespace Modules\HelpdeskEmailLog\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Modules\HelpdeskEmailLog\Tests\Fixtures\RedactedTestMail;
use Modules\HelpdeskEmailLog\Tests\Fixtures\TrackedTestMail;
use Tests\TestCase;

class BodyRedactionAndTruncationTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

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

    public function test_truncation_does_not_split_multibyte_characters(): void
    {
        // 63 bytes de límite sobre un cuerpo de 'é' (2 bytes cada uno): un
        // substr() binario cortaría el carácter nº 32 por la mitad dejando
        // UTF-8 inválido; mb_strcut debe retroceder al límite del carácter.
        config()->set('helpdeskemaillog.max_body_bytes', 63);

        Mail::raw(str_repeat('é', 40), function ($m) {
            $m->to('user@example.test')->subject('Multibyte body');
        });

        $log = EmailLog::query()->latest('id')->first();

        $this->assertNotNull($log->body_text);

        $stored = str_replace(EmailLog::TRUNCATION_MARKER, '', $log->body_text);

        $this->assertLessThanOrEqual(63, strlen($stored));
        $this->assertTrue(
            mb_check_encoding($stored, 'UTF-8'),
            'El cuerpo truncado no debe contener secuencias UTF-8 rotas.',
        );
        $this->assertSame(str_repeat('é', 31), $stored);
    }

    public function test_truncated_body_sets_metadata_flag_and_blocks_resend(): void
    {
        config()->set('helpdeskemaillog.max_body_bytes', 64);

        Mail::raw(str_repeat('B', 300), function ($m) {
            $m->to('user@example.test')->subject('Big body');
        });

        $log = EmailLog::query()->latest('id')->first();

        $this->assertTrue($log->metadata['truncated'] ?? false);
        $this->assertTrue($log->isBodyTruncated());
        $this->assertFalse($log->isResendable());
    }

    public function test_legacy_truncated_body_is_detected_by_marker(): void
    {
        // Filas anteriores al flag metadata.truncated: solo llevan el marcador.
        $log = EmailLog::factory()->create([
            'body_html' => '<p>parcial</p>'.EmailLog::TRUNCATION_MARKER,
        ]);

        $this->assertTrue($log->isBodyTruncated());
        $this->assertFalse($log->isResendable());
    }

    public function test_redacted_log_is_not_resendable(): void
    {
        Mail::to('user@example.test')->send(new RedactedTestMail);

        $log = EmailLog::query()->latest('id')->first();

        $this->assertTrue($log->isBodyRedacted());
        $this->assertFalse($log->isResendable());
    }
}
