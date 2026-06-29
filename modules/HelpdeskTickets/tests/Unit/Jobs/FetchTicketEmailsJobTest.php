<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Jobs;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Jobs\Helpdesks\FetchTicketEmailsJob;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\SlaService;
use Modules\HelpdeskTickets\Services\TicketNotificationService;
use Modules\HelpdeskTickets\Services\TicketService;
use Tests\TestCase;

// ---------------------------------------------------------------------------
// Helper subclass to expose protected methods and stub out processConnection
// ---------------------------------------------------------------------------

class TestableFetchTicketEmailsJob extends FetchTicketEmailsJob
{
    public function injectTicketService(TicketService $service): void
    {
        $this->ticketService = $service;
    }

    public function callExtractEmailAddress(string $from): string
    {
        return $this->extractEmailAddress($from);
    }

    public function callExtractEmailName(string $from): ?string
    {
        return $this->extractEmailName($from);
    }

    public function callDetectPriority(string $subject): string
    {
        return $this->detectPriority($subject);
    }

    public function callFindOrCreateTicket(array $parsed, array $connection): ?Ticket
    {
        return $this->findOrCreateTicket($parsed, $connection);
    }

    public function callProcessIncomingEmail(object $message, array $connection): void
    {
        $this->processIncomingEmail($message, $connection);
    }

    protected function processConnection(array $connection): void
    {
        // no-op — prevents real IMAP connections in tests
    }
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

class FetchTicketEmailsJobTest extends TestCase
{
    use RefreshDatabase;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        TicketStatus::firstOrCreate(
            ['slug' => 'new'],
            [
                'name' => 'Nuevo',
                'color' => '#22c55e',
                'is_open' => true,
                'is_default' => true,
                'is_system' => true,
                'stops_sla_timer' => false,
                'active' => true,
            ]
        );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeTicketService(): TicketService
    {
        $notificationService = $this->createMock(TicketNotificationService::class);
        $slaService = new SlaService($notificationService);

        return new TicketService($slaService, $notificationService);
    }

    private function makeJob(): TestableFetchTicketEmailsJob
    {
        $job = new TestableFetchTicketEmailsJob;
        $job->injectTicketService($this->makeTicketService());

        return $job;
    }

    private function seedIncomingEmailSetting(array $connections): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => 'incoming_email'],
            ['value' => json_encode(['imap' => ['connections' => $connections]])]
        );

        Cache::forget('setting_incoming_email');
    }

    private function baseConnection(array $overrides = []): array
    {
        return array_merge([
            'name' => 'test-connection',
            'host' => 'imap.example.com',
            'port' => 993,
            'username' => 'user@example.com',
            'password' => 'secret',
            'encryption' => 'ssl',
            'folder' => 'INBOX',
            'create_tickets' => false,
            'create_replies' => false,
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // handle() — filtering logic
    // -------------------------------------------------------------------------

    private function makeCountingJob(): object
    {
        return new class extends TestableFetchTicketEmailsJob
        {
            public int $count = 0;

            protected function processConnection(array $connection): void
            {
                $this->count++;
            }
        };
    }

    public function test_handle_skips_connections_with_no_create_flags(): void
    {
        $this->seedIncomingEmailSetting([
            $this->baseConnection(['create_tickets' => false, 'create_replies' => false]),
        ]);

        $job = $this->makeCountingJob();
        $job->handle($this->makeTicketService());

        $this->assertSame(0, $job->count);
    }

    public function test_handle_processes_connections_with_create_tickets(): void
    {
        $this->seedIncomingEmailSetting([
            $this->baseConnection(['create_tickets' => true, 'create_replies' => false]),
        ]);

        $job = $this->makeCountingJob();
        $job->handle($this->makeTicketService());

        $this->assertSame(1, $job->count);
    }

    public function test_handle_processes_connections_with_create_replies(): void
    {
        $this->seedIncomingEmailSetting([
            $this->baseConnection(['create_tickets' => false, 'create_replies' => true]),
        ]);

        $job = $this->makeCountingJob();
        $job->handle($this->makeTicketService());

        $this->assertSame(1, $job->count);
    }

    // -------------------------------------------------------------------------
    // extractEmailAddress — pure logic, no DB needed
    // -------------------------------------------------------------------------

    public function test_extract_email_address_from_angle_bracket_format(): void
    {
        $result = $this->makeJob()->callExtractEmailAddress('John Doe <john@example.com>');

        $this->assertSame('john@example.com', $result);
    }

    public function test_extract_email_address_plain(): void
    {
        $result = $this->makeJob()->callExtractEmailAddress('john@example.com');

        $this->assertSame('john@example.com', $result);
    }

    // -------------------------------------------------------------------------
    // extractEmailName — pure logic, no DB needed
    // -------------------------------------------------------------------------

    public function test_extract_email_name_from_display_name(): void
    {
        $result = $this->makeJob()->callExtractEmailName('John Doe <john@example.com>');

        $this->assertSame('John Doe', $result);
    }

    public function test_extract_email_name_returns_null_for_plain_email(): void
    {
        $result = $this->makeJob()->callExtractEmailName('john@example.com');

        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // detectPriority — pure logic, no DB needed
    // -------------------------------------------------------------------------

    public function test_detect_priority_urgent_keyword(): void
    {
        $this->assertSame('high', $this->makeJob()->callDetectPriority('URGENT: Help needed'));
    }

    public function test_detect_priority_low_keyword(): void
    {
        $this->assertSame('low', $this->makeJob()->callDetectPriority('low priority request'));
    }

    public function test_detect_priority_defaults_to_normal(): void
    {
        $this->assertSame('normal', $this->makeJob()->callDetectPriority('Hello there'));
    }

    // -------------------------------------------------------------------------
    // findOrCreateTicket
    // -------------------------------------------------------------------------

    public function test_find_or_create_ticket_threads_via_in_reply_to(): void
    {

        $customer = Customer::factory()->create();

        $status = TicketStatus::where('slug', 'new')->first();

        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'status_id' => $status->id,
        ]);

        $mail = TicketMail::create([
            'ticket_id' => $ticket->id,
            'direction' => 'inbound',
            'message_id' => '<original-msg-id-'.uniqid().'@example.com>',
            'from' => 'customer@example.com',
            'to' => 'support@example.com',
            'subject' => 'Help needed',
            'body_text' => 'Please help',
            'status' => 'received',
            'attachments' => [],
            'headers' => [],
        ]);

        $parsed = [
            'message_id' => '<reply-msg-id-'.uniqid().'@example.com>',
            'in_reply_to' => $mail->message_id,
            'references' => null,
            'from' => 'customer@example.com',
            'to' => 'support@example.com',
            'cc' => null,
            'bcc' => null,
            'subject' => 'Re: Help needed',
            'body_text' => 'More info',
            'body_html' => null,
            'attachments' => [],
        ];

        $result = $this->makeJob()->callFindOrCreateTicket($parsed, $this->baseConnection(['create_tickets' => false]));

        $this->assertNotNull($result);
        $this->assertSame($ticket->id, $result->id);
    }

    public function test_find_or_create_ticket_creates_new_ticket_when_no_thread(): void
    {

        Event::fake();

        $parsed = [
            'message_id' => '<new-msg-id@example.com>',
            'in_reply_to' => null,
            'references' => null,
            'from' => 'newcustomer@example.com',
            'to' => 'support@example.com',
            'cc' => null,
            'bcc' => null,
            'subject' => 'Brand new issue',
            'body_text' => 'I need help.',
            'body_html' => null,
            'attachments' => [],
        ];

        $result = $this->makeJob()->callFindOrCreateTicket($parsed, $this->baseConnection(['create_tickets' => true]));

        $this->assertInstanceOf(Ticket::class, $result);
        if ($result) {
            if ($result->customer_id) {
            }
        }
        $this->assertDatabaseHas('helpdesk_tickets', ['subject' => 'Brand new issue'], 'helpdesk');
        $this->assertDatabaseHas('helpdesk_customers', ['email' => 'newcustomer@example.com'], 'helpdesk');
    }

    public function test_find_or_create_ticket_returns_null_when_create_tickets_disabled(): void
    {

        $parsed = [
            'message_id' => '<no-thread-msg@example.com>',
            'in_reply_to' => null,
            'references' => null,
            'from' => 'stranger@example.com',
            'to' => 'support@example.com',
            'cc' => null,
            'bcc' => null,
            'subject' => 'Random email',
            'body_text' => 'Not a reply.',
            'body_html' => null,
            'attachments' => [],
        ];

        $result = $this->makeJob()->callFindOrCreateTicket($parsed, $this->baseConnection(['create_tickets' => false]));

        $this->assertNull($result);
        $this->assertDatabaseMissing('helpdesk_tickets', ['subject' => 'Random email'], 'helpdesk');
    }
}
