<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Console;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Modules\HelpdeskTickets\Jobs\Helpdesks\FetchTicketEmailsJob;
use Tests\TestCase;

class FetchEmailTicketsCommandTest extends TestCase
{
    public function test_dispatches_the_fetch_ticket_emails_job(): void
    {
        Queue::fake();

        $this->artisan('imap:emailticket')->assertSuccessful();

        Queue::assertPushed(FetchTicketEmailsJob::class);
    }

    public function test_returns_failure_when_dispatching_throws(): void
    {
        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')->andThrow(new \RuntimeException('IMAP connection unavailable'));
        $this->app->instance(Dispatcher::class, $dispatcher);

        $this->artisan('imap:emailticket')->assertFailed();
    }
}
