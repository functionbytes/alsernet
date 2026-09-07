<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\TicketChannelMailerService;
use Modules\HelpdeskTickets\Services\TicketEmailChannelsRepository;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Tests\TestCase;

class TicketChannelMailerServiceTest extends TestCase
{
    use SharesHelpdeskPdo;

    private TicketChannelMailerService $service;

    private TicketEmailChannelsRepository $channels;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        // Mismo gotcha que TicketEmailChannelsRepositoryTest: Setting::setEncrypted()
        // cachea fuera del rollback transaccional — se siembra vacío explícito.
        DB::table('settings')->updateOrInsert(
            ['key' => 'incoming_email'],
            ['value' => json_encode(['imap' => ['connections' => []]])]
        );
        Cache::forget('setting_incoming_email');

        $this->channels = new TicketEmailChannelsRepository;
        $this->service = new TicketChannelMailerService($this->channels);

        TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
    }

    private function makeTicket(): Ticket
    {
        $customer = Customer::factory()->create();

        return Ticket::create([
            'subject' => 'Test',
            'description' => 'Test description.',
            'customer_id' => $customer->id,
            'status_id' => TicketStatus::where('slug', 'open')->first()->id,
            'priority' => 'normal',
            'source' => 'email',
        ]);
    }

    public function test_resolve_channel_for_ticket_returns_null_without_inbound_mail(): void
    {
        $ticket = $this->makeTicket();

        $this->assertNull($this->service->resolveChannelForTicket($ticket));
    }

    public function test_resolve_channel_for_ticket_matches_by_recipient_mailbox(): void
    {
        $ticket = $this->makeTicket();

        TicketMail::create([
            'ticket_id' => $ticket->id,
            'direction' => 'inbound',
            'message_id' => '<abc@example.com>',
            'from' => 'cliente@example.com',
            'to' => 'info@functionbytes.com',
            'subject' => 'Ayuda',
            'status' => 'received',
        ]);

        $this->channels->create([
            'name' => 'Soporte',
            'host' => 'imap.hostinger.com',
            'port' => 993,
            'username' => 'info@functionbytes.com',
            'password' => 'secret',
            'smtp_host' => 'smtp.hostinger.com',
            'smtp_port' => 465,
            'smtp_encryption' => 'ssl',
        ]);

        $channel = $this->service->resolveChannelForTicket($ticket);

        $this->assertNotNull($channel);
        $this->assertSame('info@functionbytes.com', $channel['username']);
    }

    public function test_resolve_channel_for_ticket_returns_null_when_no_channel_matches(): void
    {
        $ticket = $this->makeTicket();

        TicketMail::create([
            'ticket_id' => $ticket->id,
            'direction' => 'inbound',
            'message_id' => '<abc@example.com>',
            'from' => 'cliente@example.com',
            'to' => 'otro-buzon@example.com',
            'subject' => 'Ayuda',
            'status' => 'received',
        ]);

        $this->channels->create([
            'name' => 'Soporte',
            'host' => 'imap.hostinger.com',
            'port' => 993,
            'username' => 'info@functionbytes.com',
            'password' => 'secret',
        ]);

        $this->assertNull($this->service->resolveChannelForTicket($ticket));
    }

    public function test_last_inbound_message_id_returns_the_most_recent_one(): void
    {
        $ticket = $this->makeTicket();

        // created_at no está en $fillable — se retrasa después de crear en vez
        // de pasarlo en create() (mass assignment lo habría ignorado en
        // silencio, dejando ambos registros con el mismo timestamp y el
        // orden de latest() librado al azar).
        $old = TicketMail::create([
            'ticket_id' => $ticket->id,
            'direction' => 'inbound',
            'message_id' => '<old@example.com>',
            'from' => 'cliente@example.com',
            'to' => 'info@functionbytes.com',
            'subject' => 'Primero',
            'status' => 'received',
        ]);
        $old->forceFill(['created_at' => now()->subHour()])->save();
        TicketMail::create([
            'ticket_id' => $ticket->id,
            'direction' => 'inbound',
            'message_id' => '<new@example.com>',
            'from' => 'cliente@example.com',
            'to' => 'info@functionbytes.com',
            'subject' => 'Segundo',
            'status' => 'received',
        ]);

        $this->assertSame('<new@example.com>', $this->service->lastInboundMessageId($ticket));
    }

    public function test_mailer_name_for_returns_null_without_smtp_host(): void
    {
        $channel = $this->channels->create([
            'name' => 'Sin SMTP',
            'host' => 'imap.hostinger.com',
            'port' => 993,
            'username' => 'info@functionbytes.com',
            'password' => 'secret',
        ]);

        $this->assertNull($this->service->mailerNameFor($channel));
    }

    public function test_mailer_name_for_registers_a_dynamic_mailer_config(): void
    {
        $channel = $this->channels->create([
            'name' => 'Con SMTP',
            'host' => 'imap.hostinger.com',
            'port' => 993,
            'username' => 'info@functionbytes.com',
            'password' => 'secret',
            'smtp_host' => 'smtp.hostinger.com',
            'smtp_port' => 465,
            'smtp_encryption' => 'ssl',
        ]);

        $mailerName = $this->service->mailerNameFor($channel);

        $this->assertNotNull($mailerName);
        $this->assertSame('smtp.hostinger.com', config("mail.mailers.{$mailerName}.host"));
        $this->assertSame('info@functionbytes.com', config("mail.mailers.{$mailerName}.username"));
    }

    /**
     * Ticket nacido de un formulario web (alsernetforms) o del widget: nunca
     * tiene un TicketMail entrante con el que correlacionar un canal. Antes
     * resolveChannelForTicket() devolvía null en este caso siempre, y la
     * confirmación salía del mailer genérico en vez del buzón real de
     * soporte (bug real, ticket TCK-2026-00093, 3-sep-2026).
     */
    public function test_resolve_channel_for_ticket_falls_back_to_the_default_channel_without_inbound_mail(): void
    {
        $ticket = $this->makeTicket();

        $channel = $this->channels->create([
            'name' => 'Soporte',
            'host' => 'imap.hostinger.com',
            'port' => 993,
            'username' => 'info@functionbytes.com',
            'password' => 'secret',
        ]);
        $this->channels->setDefault($channel['id']);

        $resolved = $this->service->resolveChannelForTicket($ticket);

        $this->assertNotNull($resolved);
        $this->assertSame('info@functionbytes.com', $resolved['username']);
    }

    public function test_resolve_channel_for_ticket_prefers_the_inbound_match_over_the_default_channel(): void
    {
        $ticket = $this->makeTicket();

        TicketMail::create([
            'ticket_id' => $ticket->id,
            'direction' => 'inbound',
            'message_id' => '<abc@example.com>',
            'from' => 'cliente@example.com',
            'to' => 'soporte-especifico@example.com',
            'subject' => 'Ayuda',
            'status' => 'received',
        ]);

        $specific = $this->channels->create([
            'name' => 'Específico',
            'host' => 'imap.example.com',
            'port' => 993,
            'username' => 'soporte-especifico@example.com',
            'password' => 'secret',
        ]);
        $default = $this->channels->create([
            'name' => 'Por defecto',
            'host' => 'imap.hostinger.com',
            'port' => 993,
            'username' => 'info@functionbytes.com',
            'password' => 'secret',
        ]);
        $this->channels->setDefault($default['id']);

        $resolved = $this->service->resolveChannelForTicket($ticket);

        $this->assertSame($specific['id'], $resolved['id']);
    }
}
