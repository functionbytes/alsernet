<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Events\MessageAdded;
use Modules\HelpdeskTickets\Listeners\SendCustomerReplyNotification;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketItem;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Modules\Mailer\Models\MailerLang;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Models\MailerTemplateLang;
use Tests\TestCase;

class SendCustomerReplyNotificationTest extends TestCase
{
    // mariadb y helpdesk apuntan a la misma BD: mismo patrón que el resto de
    // tests del módulo para evitar auto-interbloqueos de FK (ver docblock del
    // trait). Trae DatabaseTransactions consigo, no declarar aparte.
    use SharesHelpdeskPdo;

    public function test_listener_implements_should_queue(): void
    {
        $this->assertInstanceOf(
            ShouldQueue::class,
            new SendCustomerReplyNotification
        );
    }

    public function test_event_accepts_ticket_item(): void
    {
        $item = new TicketItem;
        $item->id = 1;

        $event = new MessageAdded($item);

        $this->assertInstanceOf(TicketItem::class, $event->item);
        $this->assertEquals(1, $event->item->id);
    }

    public function test_listener_skips_internal_messages(): void
    {
        Mail::fake();

        $item = new TicketItem;
        $item->is_internal = true;
        $item->user_id = 1;

        $listener = new SendCustomerReplyNotification;
        $listener->handle(new MessageAdded($item));

        Mail::assertNothingSent();
    }

    public function test_listener_skips_customer_messages_where_user_id_is_null(): void
    {
        Mail::fake();

        $item = new TicketItem;
        $item->is_internal = false;
        $item->user_id = null;

        $listener = new SendCustomerReplyNotification;
        $listener->handle(new MessageAdded($item));

        Mail::assertNothingSent();
    }

    public function test_message_added_event_stores_item_reference(): void
    {
        $item = new TicketItem;
        $item->id = 42;
        $item->is_internal = false;

        $event = new MessageAdded($item);

        $this->assertSame($item, $event->item);
        $this->assertEquals(42, $event->item->id);
    }

    /**
     * Regresión: la respuesta de agente vía TicketItem debe quedar registrada
     * en TicketMail (bandeja "Emails enviados"), con el agente que la envió.
     */
    public function test_listener_sends_mail_and_logs_ticket_mail_with_sender(): void
    {
        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        // Mail::fake() no cubre Mail::html() (MailFake no implementa ese
        // método de envío rápido — solo raw/plain), así que el listener
        // intentaría enviar de verdad. Se fuerza el driver 'array' en vez de
        // depender de MAIL_MAILER de phpunit.xml, que algo en el bootstrap de
        // la app reemplaza en runtime con la config SMTP real (ver el error
        // "sendmail: not found" si se confía solo en el env de testing).
        config(['mail.default' => 'array']);

        $langId = MailerLang::resolveDefaultId();

        $template = MailerTemplate::updateOrCreate(
            ['key' => 'helpdesk.ticket_reply'],
            ['name' => 'Ticket reply', 'module' => 'helpdesktickets', 'is_enabled' => true]
        );
        MailerTemplateLang::updateOrCreate(
            ['mailer_template_id' => $template->id, 'lang_id' => $langId],
            ['subject' => 'Re: {{TICKET_NUMBER}}', 'content' => 'Hola {{CUSTOMER_NAME}}, {{AGENT_NAME}} te responde.']
        );

        $status = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
        $customer = Customer::factory()->create();
        $agent = User::factory()->create();
        $ticket = Ticket::create([
            'subject' => 'Reply notification test',
            'description' => 'Test description.',
            'customer_id' => $customer->id,
            'status_id' => $status->id,
            'priority' => 'normal',
            'source' => 'web',
        ]);

        $item = TicketItem::create([
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'type' => 'message',
            'body' => 'Aquí tienes la respuesta.',
            'is_internal' => false,
        ]);

        (new SendCustomerReplyNotification)->handle(new MessageAdded($item));

        $this->assertDatabaseHas('helpdesk_ticket_mails', [
            'ticket_id' => $ticket->id,
            'ticket_item_id' => $item->id,
            'user_id' => $agent->id,
            'direction' => 'outbound',
            'to' => $customer->email,
            'status' => 'sent',
        ], 'helpdesk');
    }

    private function helpdeskConnectionAvailable(): bool
    {
        try {
            DB::connection('helpdesk')->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
