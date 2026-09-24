<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Console;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Modules\HelpdeskTickets\Events\SlaBreached;
use Modules\HelpdeskTickets\Listeners\SendSlaBreachNotification;
use Modules\HelpdeskTickets\Mail\SlaBreachMail;
use Modules\HelpdeskTickets\Mail\SlaDigestMail;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\TeamChannelNotifier;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Volumen de avisos de SLA (24-sep-2026): el agente recibe su aviso al
 * momento y los managers un resumen periódico (ticket:sla-digest).
 */
class SlaDigestTest extends TestCase
{
    use SharesHelpdeskPdo;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        // El canal del equipo es un webhook real si está configurado.
        $this->mock(TeamChannelNotifier::class)->shouldReceive('notify')->andReturn([]);
        config([
            'helpdesktickets.sla_alerts.managers_digest' => true,
            'helpdesktickets.sla_alerts.digest_hours' => 4,
        ]);

        Permission::firstOrCreate(['name' => 'manage_helpdesk', 'guard_name' => 'web']);
        $this->manager = User::factory()->create();
        $this->manager->givePermissionTo('manage_helpdesk');
    }

    public function test_el_aviso_de_incumplimiento_va_al_agente_y_no_a_los_managers(): void
    {
        $agent = User::factory()->create();
        $ticket = $this->ticketVencido(['assignee_id' => $agent->id]);

        app(SendSlaBreachNotification::class)->handle(new SlaBreached($ticket));

        Mail::assertQueued(SlaBreachMail::class, fn ($mail) => $mail->hasTo($agent->email));
        Mail::assertNotQueued(SlaBreachMail::class, fn ($mail) => $mail->hasTo($this->manager->email));
    }

    public function test_sin_agente_el_aviso_sigue_llegando_a_los_managers(): void
    {
        $ticket = $this->ticketVencido(['assignee_id' => null]);

        app(SendSlaBreachNotification::class)->handle(new SlaBreached($ticket));

        Mail::assertQueued(SlaBreachMail::class, fn ($mail) => $mail->hasTo($this->manager->email));
    }

    public function test_el_resumen_se_envia_una_vez_y_no_se_repite_si_la_lista_no_cambia(): void
    {
        $previous = [Cache::get('helpdesktickets:sla-digest:last-sent'), Cache::get('helpdesktickets:sla-digest:signature')];

        try {
            Cache::forget('helpdesktickets:sla-digest:last-sent');
            Cache::forget('helpdesktickets:sla-digest:signature');
            $this->ticketVencido(['assignee_id' => null]);

            $this->artisan('ticket:sla-digest')->assertSuccessful();
            Mail::assertQueued(SlaDigestMail::class, fn ($mail) => $mail->hasTo($this->manager->email));

            // Pasado el intervalo, con la misma lista, no hay nada nuevo que contar.
            Cache::put('helpdesktickets:sla-digest:last-sent', now()->subHours(5), 60);
            Mail::fake();
            $this->artisan('ticket:sla-digest')->assertSuccessful();
            Mail::assertNotQueued(SlaDigestMail::class);
        } finally {
            // La caché vive en Redis, fuera de la transacción del test.
            foreach (['last-sent', 'signature'] as $i => $key) {
                $previous[$i] === null
                    ? Cache::forget('helpdesktickets:sla-digest:'.$key)
                    : Cache::put('helpdesktickets:sla-digest:'.$key, $previous[$i], now()->addDays(2));
            }
        }
    }

    private function ticketVencido(array $overrides): Ticket
    {
        $status = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Abierto', 'color' => '#90bb13', 'is_open' => true, 'is_default' => false, 'order' => 2]
        );

        return Ticket::create(array_merge([
            'subject' => 'SLA vencido',
            'description' => 'x',
            'status_id' => $status->id,
            'priority' => 'normal',
            'source' => 'web',
            'sla_resolution_due_at' => now()->subHour(),
            'sla_resolution_breached' => true,
        ], $overrides));
    }
}
