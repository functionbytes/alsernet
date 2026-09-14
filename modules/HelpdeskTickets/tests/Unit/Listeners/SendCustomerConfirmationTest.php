<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Listeners\SendCustomerConfirmation;
use Modules\HelpdeskTickets\Mail\TicketCreatedMail;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\Locales\Models\Locale;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Models\MailerTemplateLang;
use Tests\TestCase;

class SendCustomerConfirmationTest extends TestCase
{
    use DatabaseTransactions;

    // 'mysql' es obligatorio: MailerTemplate/MailerTemplateLang usan esa
    // conexión (default de la app), no 'mariadb'/'helpdesk'. Sin ella, el
    // create()+delete() de createEnabledTemplate()/tearDown() no se revierte
    // al terminar el test y borra/pisa la plantilla REAL de producción — pasó
    // de verdad (29-ago-2026, "helpdesk_tickets.ticket_created" desapareció
    // tras correr este archivo). Ver reference_helpdesktickets_settings_mysql_connection_missing.
    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private function createTicketWithCustomer(string $email, string $description = 'Mi pedido no ha llegado todavía.'): Ticket
    {
        $customerId = DB::connection('helpdesk')->table('helpdesk_customers')->insertGetId([
            'email' => $email,
            'name' => 'Cliente Test',
            'language' => '',
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        $ticketId = DB::connection('helpdesk')->table('helpdesk_tickets')->insertGetId([
            'ticket_number' => 'TCK-CONFIRM-'.uniqid(),
            'subject' => 'Pedido incompleto',
            'description' => $description,
            'priority' => 'normal',
            'source' => 'email',
            'customer_id' => $customerId,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        return Ticket::on('helpdesk')->with('customer')->findOrFail($ticketId);
    }

    private function createTicketWithoutCustomer(): Ticket
    {
        // A diferencia de TicketReopened, el constructor de TicketCreated hace
        // $ticket->load(['customer', ...]) — anular el email en memoria no
        // sirve porque se recarga desde BD. customer_id sí es nullable.
        $ticketId = DB::connection('helpdesk')->table('helpdesk_tickets')->insertGetId([
            'ticket_number' => 'TCK-CONFIRM-'.uniqid(),
            'subject' => 'Sin cliente',
            'description' => 'N/A',
            'priority' => 'normal',
            'source' => 'email',
            'customer_id' => null,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        return Ticket::on('helpdesk')->findOrFail($ticketId);
    }

    private function ensureTestLang(): int
    {
        $langId = DB::table('langs')->where('available', 1)->value('id');

        if (! $langId) {
            $langId = DB::table('langs')->insertGetId([
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

        Locale::clearResolvedLegacyLangId();

        return (int) $langId;
    }

    private function createEnabledTemplate(): MailerTemplate
    {
        $langId = $this->ensureTestLang();

        // updateOrCreate, no create(): con 'mysql' en connectionsToTransact la
        // plantilla REAL sembrada (misma key) es visible dentro de la
        // transacción del test — ver mismo comentario en
        // SendCustomerReopenNotificationTest.
        $template = MailerTemplate::updateOrCreate(
            ['key' => 'helpdesk_tickets.ticket_created'],
            ['name' => 'Ticket recibido (test)', 'is_enabled' => true, 'is_protected' => false, 'module' => 'helpdesktickets']
        );

        MailerTemplateLang::updateOrCreate(
            ['mailer_template_id' => $template->id, 'lang_id' => $langId],
            [
                'subject' => 'Hemos recibido tu solicitud — #{TICKET_NUMBER}',
                'content' => '<p>{TICKET_SUBJECT}</p><p>#{TICKET_NUMBER}</p>',
            ]
        );

        return $template;
    }

    protected function tearDown(): void
    {
        MailerTemplate::where('key', 'helpdesk_tickets.ticket_created')->delete();
        Locale::clearResolvedLegacyLangId();

        parent::tearDown();
    }

    // ─── structural contracts ─────────────────────────────────────────────────

    public function test_listener_implements_should_queue(): void
    {
        $this->assertInstanceOf(ShouldQueue::class, app(SendCustomerConfirmation::class));
    }

    public function test_listener_is_on_notifications_queue(): void
    {
        // Se comprueba EXACTAMENTE como lo hace el Dispatcher de Laravel:
        // instancia sin constructor + viaQueue(). Este test pasaba antes
        // leyendo ->queue sobre una instancia resuelta del contenedor (con
        // constructor), y por eso no detectó que en producción el job
        // acababa en la cola 'default' —que ningún worker atiende— porque
        // la asignación vivía dentro del constructor.
        $listener = (new \ReflectionClass(SendCustomerConfirmation::class))->newInstanceWithoutConstructor();

        $this->assertSame('notifications', $listener->viaQueue());
    }

    public function test_listener_retries_three_times(): void
    {
        $this->assertEquals(3, app(SendCustomerConfirmation::class)->tries);
    }

    // ─── handle ───────────────────────────────────────────────────────────────

    public function test_sends_confirmation_and_records_ticket_mail(): void
    {
        Mail::fake();
        $this->createEnabledTemplate();

        $ticket = $this->createTicketWithCustomer('cliente@confirm-test.com', 'Mi pedido no ha llegado todavía.');

        app(SendCustomerConfirmation::class)->handle(new TicketCreated($ticket));

        // Ya no se pasa MESSAGE_PREVIEW (era el volcado crudo de los campos del
        // formulario en tickets de alsernetforms, no el mensaje real del
        // cliente — quitado a petición del usuario, 3-sep-2026). El contenido
        // se verifica ahora contra TICKET_SUBJECT/TICKET_NUMBER, que sí siguen
        // pasándose.
        Mail::assertQueued(
            TicketCreatedMail::class,
            fn ($mail) => str_contains($mail->emailContent, $ticket->subject)
                && str_contains($mail->emailContent, $ticket->ticket_number)
                && $mail->hasTo('cliente@confirm-test.com')
        );

        // Registro en TicketMail para trazabilidad, igual que la respuesta del
        // agente y el cambio de estado — antes esta vía no dejaba rastro.
        $this->assertDatabaseHas('helpdesk_ticket_mails', [
            'ticket_id' => $ticket->id,
            'direction' => 'outbound',
            'to' => 'cliente@confirm-test.com',
        ], 'helpdesk');
    }

    public function test_skips_when_customer_has_no_email(): void
    {
        Mail::fake();
        $this->createEnabledTemplate();

        $ticket = $this->createTicketWithoutCustomer();

        app(SendCustomerConfirmation::class)->handle(new TicketCreated($ticket));

        Mail::assertNothingQueued();
    }
}
