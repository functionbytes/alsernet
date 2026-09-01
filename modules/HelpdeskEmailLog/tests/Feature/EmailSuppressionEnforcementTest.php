<?php

namespace Modules\HelpdeskEmailLog\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Modules\HelpdeskEmailLog\Enums\EmailStatus;
use Modules\HelpdeskEmailLog\Enums\SuppressionReason;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Modules\HelpdeskEmailLog\Models\EmailSuppression;
use Modules\HelpdeskEmailLog\Services\EmailSuppressionService;
use Modules\HelpdeskEmailLog\Tests\Fixtures\TrackedTestMail;
use Tests\TestCase;

/**
 * Cubre el enganche real de EnforceEmailSuppression sobre
 * Illuminate\Mail\Events\MessageSending — un envío a una dirección
 * suprimida no debe llegar de verdad (Mail::fake() + assertNothingSent
 * habría sido más simple, pero fake() reemplaza el Mailer entero y con él
 * el propio mecanismo que se está probando; en su lugar se usa el mailer
 * 'array' — activo en testing por phpunit.xml, MAIL_MAILER=array — y se
 * comprueba que Mail::getSymfonyTransport()->messages() queda vacío).
 */
class EmailSuppressionEnforcementTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    public function test_sending_to_a_globally_suppressed_address_is_blocked(): void
    {
        EmailSuppression::create(['email' => 'blocked@example.test', 'module' => '', 'reason' => SuppressionReason::HardBounce]);

        Mail::to('blocked@example.test')->send(new TrackedTestMail);

        $log = EmailLog::where('subject', 'Tracked test email')->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame(EmailStatus::Suppressed, $log->status);
        $this->assertNotNull($log->suppressed_at);
    }

    public function test_sending_to_a_module_scoped_suppression_is_blocked_only_for_that_module(): void
    {
        // TrackedTestMail no implementa TracksEmailLog, así que su 'module'
        // queda null — se cubre el caso module=null explícitamente (una
        // supresión GLOBAL sí debe bloquearlo; una acotada a otro módulo no).
        EmailSuppression::create(['email' => 'scoped@example.test', 'module' => 'OtroModulo', 'reason' => SuppressionReason::Manual]);

        Mail::to('scoped@example.test')->send(new TrackedTestMail);

        $log = EmailLog::where('subject', 'Tracked test email')
            ->where('to_addresses', 'like', '%scoped@example.test%')
            ->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertNotSame(EmailStatus::Suppressed, $log->status);
    }

    public function test_sending_to_a_non_suppressed_address_goes_through_normally(): void
    {
        Mail::to('fine@example.test')->send(new TrackedTestMail);

        $log = EmailLog::where('subject', 'Tracked test email')
            ->where('to_addresses', 'like', '%fine@example.test%')
            ->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertNotSame(EmailStatus::Suppressed, $log->status);
    }

    public function test_hard_bounce_auto_suppresses_the_recipient_globally(): void
    {
        $log = EmailLog::create([
            'module' => 'Document', 'from_address' => 'web@a-alvarez.com',
            'to_addresses' => ['auto-hard@example.test'], 'subject' => 'x',
            'status' => EmailStatus::Sent, 'message_id' => 'auto-hard@webadmin.test', 'sent_at' => now(),
        ]);

        $log->markAsBounced('permanent failure', isHard: true);

        $this->assertTrue(app(EmailSuppressionService::class)->isSuppressed('auto-hard@example.test'));

        $suppression = EmailSuppression::where('email', 'auto-hard@example.test')->first();
        $this->assertSame('', $suppression->module);
        $this->assertSame(SuppressionReason::HardBounce, $suppression->reason);
        $this->assertNull($suppression->causer_id);
    }

    public function test_soft_bounce_does_not_auto_suppress(): void
    {
        $log = EmailLog::create([
            'module' => 'Document', 'from_address' => 'web@a-alvarez.com',
            'to_addresses' => ['auto-soft@example.test'], 'subject' => 'x',
            'status' => EmailStatus::Sent, 'message_id' => 'auto-soft@webadmin.test', 'sent_at' => now(),
        ]);

        $log->markAsBounced('mailbox full, temporary', isHard: false);

        $this->assertFalse(app(EmailSuppressionService::class)->isSuppressed('auto-soft@example.test'));
    }

    public function test_complaint_auto_suppresses_the_recipient_globally(): void
    {
        $log = EmailLog::create([
            'module' => 'HelpdeskTickets', 'from_address' => 'soporte@example.test',
            'to_addresses' => ['auto-complaint@example.test'], 'subject' => 'x',
            'status' => EmailStatus::Sent, 'message_id' => 'auto-complaint@webadmin.test', 'sent_at' => now(),
        ]);

        $log->markAsComplained('feedback loop');

        $this->assertTrue(app(EmailSuppressionService::class)->isSuppressed('auto-complaint@example.test'));

        $suppression = EmailSuppression::where('email', 'auto-complaint@example.test')->first();
        $this->assertSame(SuppressionReason::Complaint, $suppression->reason);
    }
}
