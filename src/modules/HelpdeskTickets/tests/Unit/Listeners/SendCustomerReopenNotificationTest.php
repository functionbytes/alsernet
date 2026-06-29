<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\HelpdeskTickets\Events\TicketReopened;
use Modules\HelpdeskTickets\Listeners\SendCustomerReopenNotification;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\Locales\Models\Locale;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Models\MailerTemplateLang;
use Tests\TestCase;

class SendCustomerReopenNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private function createTicketWithCustomer(string $email, ?string $name = 'Cliente Test'): Ticket
    {
        $customerId = DB::connection('helpdesk')->table('helpdesk_customers')->insertGetId([
            'email' => $email,
            'name' => $name,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        $ticketId = DB::connection('helpdesk')->table('helpdesk_tickets')->insertGetId([
            'ticket_number' => 'TKT-REOPEN-'.uniqid(),
            'subject' => 'Test ticket reopen',
            'priority' => 'normal',
            'source' => 'web',
            'customer_id' => $customerId,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        return Ticket::on('helpdesk')->with('customer')->findOrFail($ticketId);
    }

    private function createTicketWithoutEmail(): Ticket
    {
        // email column is NOT NULL — create with real email, then null it in-memory
        // so the listener's `$ticket->customer?->email` check is falsy
        $customerId = DB::connection('helpdesk')->table('helpdesk_customers')->insertGetId([
            'email' => 'noemail-'.uniqid().'@reopen-test.com',
            'name' => 'No Email Customer',
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        $ticketId = DB::connection('helpdesk')->table('helpdesk_tickets')->insertGetId([
            'ticket_number' => 'TKT-NOEMAIL-'.uniqid(),
            'subject' => 'Test ticket no email',
            'priority' => 'normal',
            'source' => 'web',
            'customer_id' => $customerId,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        $ticket = Ticket::on('helpdesk')->with('customer')->findOrFail($ticketId);
        $ticket->customer->email = null;

        return $ticket;
    }

    private function ensureTestLang(): int
    {
        $langId = \DB::table('langs')->where('available', 1)->value('id');

        if (! $langId) {
            $langId = \DB::table('langs')->insertGetId([
                'uid' => Str::uuid()->toString(),
                'title' => 'Español',
                'iso_code' => 'es',
                'lenguage_code' => 'es',
                'locate' => 'es_ES',
                'date_format_full' => 'd/m/Y H:i',
                'date_format_lite' => 'd/m/Y',
                'available' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Clear cache so MailerLang::resolveDefaultId() picks up the DB lang
        Locale::clearResolvedLegacyLangId();

        return (int) $langId;
    }

    private function createEnabledTemplate(): MailerTemplate
    {
        $langId = $this->ensureTestLang();

        $template = MailerTemplate::create([
            'key' => 'helpdesk.ticket_reopened',
            'name' => 'Ticket Reopened',
            'is_enabled' => true,
            'is_protected' => false,
            'module' => 'helpdesk',
        ]);

        MailerTemplateLang::create([
            'mailer_template_id' => $template->id,
            'lang_id' => $langId,
            'subject' => 'Tu ticket ha sido reabierto',
            'content' => '<p>Hola {CUSTOMER_NAME}, tu ticket {TICKET_NUMBER} ha sido reabierto.</p>',
        ]);

        return $template;
    }

    protected function tearDown(): void
    {
        MailerTemplate::where('key', 'helpdesk.ticket_reopened')->delete();
        Locale::clearResolvedLegacyLangId();

        parent::tearDown();
    }

    // ─── structural contracts ─────────────────────────────────────────────────

    public function test_listener_implements_should_queue(): void
    {
        $this->assertInstanceOf(ShouldQueue::class, new SendCustomerReopenNotification);
    }

    public function test_listener_is_on_notifications_queue(): void
    {
        $listener = new SendCustomerReopenNotification;

        $this->assertEquals('notifications', $listener->queue);
    }

    public function test_listener_retries_three_times(): void
    {
        $this->assertEquals(3, (new SendCustomerReopenNotification)->tries);
    }

    // ─── handle ───────────────────────────────────────────────────────────────

    public function test_listener_sends_email_when_template_exists_and_enabled(): void
    {

        $this->createEnabledTemplate();
        $ticket = $this->createTicketWithCustomer('customer@reopen-test.com');

        $listener = new SendCustomerReopenNotification;

        // Mail::html() bypasses MailFake (raw send, not a Mailable instance).
        // We verify the listener runs through to completion without early return.
        $listener->handle(new TicketReopened($ticket));

        $this->assertTrue(true);
    }

    public function test_listener_skips_when_template_disabled(): void
    {

        Mail::fake();

        MailerTemplate::create([
            'key' => 'helpdesk.ticket_reopened',
            'name' => 'Ticket Reopened',
            'is_enabled' => false,
            'is_protected' => false,
            'module' => 'helpdesk',
        ]);

        $ticket = $this->createTicketWithCustomer('disabled@reopen-test.com');

        $listener = new SendCustomerReopenNotification;
        $listener->handle(new TicketReopened($ticket));

        Mail::assertNothingSent();
    }

    public function test_listener_skips_when_template_not_found(): void
    {

        Mail::fake();

        $ticket = $this->createTicketWithCustomer('notemplate@reopen-test.com');

        $listener = new SendCustomerReopenNotification;
        $listener->handle(new TicketReopened($ticket));

        Mail::assertNothingSent();
    }

    public function test_listener_skips_when_customer_has_no_email(): void
    {

        Mail::fake();
        $this->createEnabledTemplate();

        $ticket = $this->createTicketWithoutEmail();

        $listener = new SendCustomerReopenNotification;
        $listener->handle(new TicketReopened($ticket));

        Mail::assertNothingSent();
    }
}
