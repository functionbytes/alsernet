<?php

namespace Modules\HelpdeskEmailActivity\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Modules\HelpdeskEmailActivity\Tests\Fixtures\TrackedTestMail;
use Tests\TestCase;

/**
 * Regression coverage for the `emaillog.integration_enabled` admin toggle
 * (panel/settings/helpdesk/integrations) on LogEmailQueued/LogEmailSent.
 * The real mail send must never be affected — only whether an EmailLog row
 * gets created.
 */
class EmailLogIntegrationToggleTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = [null, 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        // phpunit.xml fuerza MAIL_MAILER=array (force="true"), pero dentro de
        // Docker getenv()/env() ignoran ese force y siguen devolviendo 'smtp'
        // real, así que config('mail.default') caía en 'smtp' y Mail::to()->send()
        // usaba el transporte SMTP real en vez del fake 'array' durante el test.
        config(['mail.default' => 'array']);

        // Mismo mismatch getenv()/Docker para QUEUE_CONNECTION: LogEmailSent
        // implementa ShouldQueue, así que sin este fix se encolaba en Redis
        // real y un worker real (proceso distinto, sin la transacción de este
        // test) podía crear filas sin rollback más tarde. Ver memoria
        // reference_phpunit_docker_getenv_mismatch_five_vars.
        config(['queue.default' => 'sync']);
    }

    protected function tearDown(): void
    {
        Setting::set('emaillog.integration_enabled', '1', 'integrations');

        parent::tearDown();
    }

    public function test_no_log_is_recorded_when_toggle_is_disabled(): void
    {
        Setting::set('emaillog.integration_enabled', '0', 'integrations');

        // assertDatabaseMissing('email_logs', ['subject' => '...']) es poco
        // fiable aquí: 'Tracked test email' es el asunto por defecto del
        // fixture TrackedTestMail, usado por MUCHOS tests de este módulo —
        // hay filas reales preexistentes (159 en la última corrida) de
        // ejecuciones pasadas/otras sesiones en esta BD compartida, así que
        // el assert fallaba SIEMPRE aunque el toggle bloqueara el log nuevo
        // correctamente. Se compara en su lugar el máximo id ANTES/DESPUÉS
        // del envío — inmune a la contaminación histórica.
        $maxIdBefore = (int) (EmailLog::max('id') ?? 0);

        Mail::to('person@example.test')->send(new TrackedTestMail);

        $this->assertFalse(
            EmailLog::where('id', '>', $maxIdBefore)->where('subject', 'Tracked test email')->exists(),
            'El toggle desactivado no debe crear ningún EmailLog nuevo.',
        );
    }

    public function test_internal_headers_are_still_stripped_when_toggle_is_disabled(): void
    {
        Setting::set('emaillog.integration_enabled', '0', 'integrations');

        Mail::to('person@example.test')->send(new TrackedTestMail);

        $sent = Mail::getSymfonyTransport()->messages();
        $headers = $sent->first()->getOriginalMessage()->getHeaders();

        foreach (['X-Email-Module', 'X-Entity-Type', 'X-Entity-Id', 'X-Mailable-Class'] as $header) {
            $this->assertFalse($headers->has($header), "Header {$header} should have been stripped.");
        }
    }

    public function test_log_is_recorded_when_toggle_is_enabled(): void
    {
        Setting::set('emaillog.integration_enabled', '1', 'integrations');

        Mail::to('person@example.test')->send(new TrackedTestMail);

        $this->assertDatabaseHas('email_logs', [
            'subject' => 'Tracked test email',
        ]);
    }
}
