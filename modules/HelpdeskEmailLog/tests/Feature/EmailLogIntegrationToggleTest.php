<?php

namespace Modules\HelpdeskEmailLog\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskEmailLog\Tests\Fixtures\TrackedTestMail;
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

    protected function tearDown(): void
    {
        Setting::set('emaillog.integration_enabled', '1', 'integrations');

        parent::tearDown();
    }

    public function test_no_log_is_recorded_when_toggle_is_disabled(): void
    {
        Setting::set('emaillog.integration_enabled', '0', 'integrations');

        Mail::to('person@example.test')->send(new TrackedTestMail);

        $this->assertDatabaseMissing('email_logs', [
            'subject' => 'Tracked test email',
        ]);
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
