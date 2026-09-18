<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use Illuminate\Support\Facades\Bus;
use Modules\HelpdeskTickets\Jobs\SendSlaWarnings as SendSlaWarningsJob;
use Tests\TestCase;

/**
 * The `tickets:sla-warnings` command used to duplicate the SLA logic with a
 * notification/Mailable that never existed (fatal "class not found" when run).
 * It now delegates to the canonical job — the single source of truth also used
 * by the 30-min scheduler.
 */
class SendSlaWarningsCommandTest extends TestCase
{
    public function test_command_delegates_to_the_canonical_job(): void
    {
        Bus::fake();

        $this->artisan('tickets:sla-warnings')->assertSuccessful();

        Bus::assertDispatchedSync(SendSlaWarningsJob::class);
    }
}
