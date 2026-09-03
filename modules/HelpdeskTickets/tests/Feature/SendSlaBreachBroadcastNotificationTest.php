<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Modules\Core\Models\Setting;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Events\TicketSlaBreached as TicketSlaBreachedEvent;
use Modules\HelpdeskTickets\Listeners\SendSlaBreachBroadcastNotification;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketSlaBreach;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Tests\TestCase;

/**
 * Modal 31 "Notificaciones": un SLA incumplido ahora también avisa al canal
 * de equipo (Slack/Teams) configurado, además de al agente asignado de
 * siempre. Se llama al listener directamente (no Event::dispatch()) porque
 * TicketSlaBreached implementa ShouldBroadcast y su broadcastWith() asume
 * category/status/customer ya cargados -- no hace falta ese aparato para
 * probar el listener en sí.
 */
class SendSlaBreachBroadcastNotificationTest extends TestCase
{
    use SharesHelpdeskPdo;

    private TicketStatus $status;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        Setting::set('tickets.slack_webhook_url', '');
        Setting::set('tickets.teams_webhook_url', '');

        $this->status = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
        $this->customer = Customer::factory()->create();
    }

    public function test_avisa_al_canal_de_equipo_cuando_hay_un_webhook_configurado(): void
    {
        Notification::fake();
        Http::fake(['*' => Http::response('', 200)]);
        Setting::setEncrypted('tickets.slack_webhook_url', 'https://1.1.1.1/hook');

        $agent = User::factory()->create();
        $ticket = $this->makeTicket(['assignee_id' => $agent->id]);
        $breach = $this->makeBreach($ticket);

        app(SendSlaBreachBroadcastNotification::class)->handle(new TicketSlaBreachedEvent($ticket, $breach));

        Http::assertSent(fn ($request) => $request->url() === 'https://1.1.1.1/hook'
            && str_contains($request['text'], $ticket->ticket_number));
    }

    public function test_no_hace_ninguna_peticion_sin_webhook_configurado(): void
    {
        Notification::fake();
        Http::fake();

        $agent = User::factory()->create();
        $ticket = $this->makeTicket(['assignee_id' => $agent->id]);
        $breach = $this->makeBreach($ticket);

        app(SendSlaBreachBroadcastNotification::class)->handle(new TicketSlaBreachedEvent($ticket, $breach));

        Http::assertNothingSent();
    }

    public function test_avisa_al_canal_de_equipo_incluso_sin_agente_asignado(): void
    {
        // Un SLA roto sin nadie asignado es justo el caso que más le
        // interesa al canal del equipo, no menos.
        Notification::fake();
        Http::fake(['*' => Http::response('', 200)]);
        Setting::setEncrypted('tickets.slack_webhook_url', 'https://1.1.1.1/hook');

        $ticket = $this->makeTicket(['assignee_id' => null]);
        $breach = $this->makeBreach($ticket);

        app(SendSlaBreachBroadcastNotification::class)->handle(new TicketSlaBreachedEvent($ticket, $breach));

        Http::assertSent(fn ($request) => $request->url() === 'https://1.1.1.1/hook');
    }

    private function makeTicket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Ticket SLA',
            'description' => 'x',
            'customer_id' => $this->customer->id,
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'web',
        ], $overrides));
    }

    private function makeBreach(Ticket $ticket): TicketSlaBreach
    {
        return TicketSlaBreach::create([
            'ticket_id' => $ticket->id,
            'breach_type' => 'first_response',
            'due_at' => now()->subHour(),
            'breached_at' => now(),
            'breach_duration_minutes' => 60,
            'resolved' => false,
        ]);
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
