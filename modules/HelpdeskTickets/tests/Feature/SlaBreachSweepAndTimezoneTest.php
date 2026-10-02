<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Modules\HelpdeskTickets\Events\SlaBreached;
use Modules\HelpdeskTickets\Events\TicketSlaBreached;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketSlaBreach;
use Modules\HelpdeskTickets\Models\TicketSlaPolicy;
use Modules\HelpdeskTickets\Services\SlaService;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Tests\TestCase;

/**
 * Barrido de primera/siguiente respuesta en SlaService::checkBreaches() (sin
 * depender del toggle de ticket:autooverdue), registro atómico, cálculo de
 * horas hábiles con zona horaria distinta a la de la app y reanudación en
 * minutos hábiles.
 */
class SlaBreachSweepAndTimezoneTest extends TestCase
{
    use SharesHelpdeskPdo;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_sweep_flags_first_response_breach_and_records_it_once(): void
    {
        Event::fake([TicketSlaBreached::class, SlaBreached::class]);

        $ticket = Ticket::factory()->create();
        Ticket::query()->whereKey($ticket->id)->update([
            'first_response_at' => null,
            'sla_first_response_due_at' => now()->subHour(),
            'sla_first_response_breached' => false,
        ]);

        app(SlaService::class)->checkBreaches();
        app(SlaService::class)->checkBreaches();

        $this->assertTrue($ticket->fresh()->sla_first_response_breached);
        $this->assertSame(1, TicketSlaBreach::query()->where('ticket_id', $ticket->id)->where('breach_type', 'first_response')->count());
        // El barrido recorre toda la tabla (BD compartida): solo cuenta los avisos de este ticket.
        $this->assertCount(1, Event::dispatched(TicketSlaBreached::class, fn (TicketSlaBreached $e) => $e->ticket->id === $ticket->id));
        Event::assertNotDispatched(SlaBreached::class, fn (SlaBreached $e) => $e->ticket->id === $ticket->id);
    }

    public function test_sweep_skips_answered_closed_and_paused_first_responses(): void
    {
        Event::fake([TicketSlaBreached::class, SlaBreached::class]);

        $answered = Ticket::factory()->create();
        $closed = Ticket::factory()->create();
        $paused = Ticket::factory()->create();

        foreach ([$answered, $closed, $paused] as $ticket) {
            Ticket::query()->whereKey($ticket->id)->update([
                'first_response_at' => null,
                'sla_first_response_due_at' => now()->subHour(),
                'sla_first_response_breached' => false,
            ]);
        }
        Ticket::query()->whereKey($answered->id)->update(['first_response_at' => now()->subMinutes(10)]);
        Ticket::query()->whereKey($closed->id)->update(['closed_at' => now()->subMinute()]);
        Ticket::query()->whereKey($paused->id)->update(['sla_paused_at' => now()->subMinute()]);

        app(SlaService::class)->checkBreaches();

        foreach ([$answered, $closed, $paused] as $ticket) {
            $this->assertFalse($ticket->fresh()->sla_first_response_breached);
        }
    }

    public function test_sweep_flags_next_response_breach_even_with_toggle_off(): void
    {
        Event::fake([TicketSlaBreached::class]);

        $ticket = Ticket::factory()->create();
        Ticket::query()->whereKey($ticket->id)->update([
            'sla_next_response_due_at' => now()->subHour(),
            'sla_next_response_breached' => false,
        ]);

        app(SlaService::class)->checkBreaches();

        $this->assertTrue($ticket->fresh()->sla_next_response_breached);
        $this->assertSame(1, TicketSlaBreach::query()->where('ticket_id', $ticket->id)->where('breach_type', 'next_response')->count());
    }

    public function test_registration_is_atomic_when_flag_was_claimed_by_another_process(): void
    {
        Event::fake([TicketSlaBreached::class, SlaBreached::class]);

        $ticket = Ticket::factory()->create();
        Ticket::query()->whereKey($ticket->id)->update([
            'sla_resolution_due_at' => now()->subHour(),
            'sla_resolution_breached' => false,
        ]);

        // Instancia obsoleta (flag en memoria a false) mientras otro proceso
        // ya lo marcó en base de datos.
        $stale = Ticket::query()->findOrFail($ticket->id);
        Ticket::query()->whereKey($ticket->id)->update(['sla_resolution_breached' => true]);

        $this->assertFalse(app(SlaService::class)->registerResolutionBreach($stale));
        Event::assertNotDispatched(SlaBreached::class);
    }

    public function test_business_hours_due_date_is_not_shifted_by_policy_timezone(): void
    {
        $this->assertSame('UTC', config('app.timezone'));

        foreach (['Asia/Tokyo', 'Europe/Madrid'] as $timezone) {
            $policy = TicketSlaPolicy::factory()->create([
                'business_hours_only' => true,
                'timezone' => $timezone,
                'business_hours' => ['monday' => ['start' => '09:00', 'end' => '17:00']],
            ]);
            $ticket = Ticket::factory()->make(['priority' => 'normal']);
            $ticket->setRelation('slaPolicy', $policy);

            // Lunes 10:00 en la zona de la política, 60 min hábiles después.
            $start = Carbon::parse('2030-01-07 10:00:00', $timezone)->setTimezone('UTC');
            $due = $this->invokeCalculateBusinessTime($ticket, $start, 60, $policy);

            $this->assertSame('UTC', $due->getTimezone()->getName(), $timezone);
            $this->assertSame(
                Carbon::parse('2030-01-07 11:00:00', $timezone)->utc()->toDateTimeString(),
                $due->toDateTimeString(),
                $timezone
            );
        }
    }

    public function test_resume_sla_with_business_hours_policy_does_not_extend_over_weekend(): void
    {
        $policy = TicketSlaPolicy::factory()->create(['business_hours_only' => true]);
        $ticket = Ticket::factory()->create();

        $due = Carbon::parse('2030-01-15 12:00:00');
        Ticket::query()->whereKey($ticket->id)->update([
            'sla_policy_id' => $policy->id,
            'sla_resolution_due_at' => $due,
            // Domingo de madrugada a domingo noche: fuera de horario en cualquier
            // calendario europeo (19 h naturales, 0 hábiles).
            'sla_paused_at' => Carbon::parse('2030-01-13 01:00:00'),
        ]);

        Carbon::setTestNow(Carbon::parse('2030-01-13 20:00:00'));

        $ticket = Ticket::query()->findOrFail($ticket->id);
        app(SlaService::class)->resumeSla($ticket);

        $this->assertNull($ticket->fresh()->sla_paused_at);
        $this->assertSame($due->toDateTimeString(), $ticket->fresh()->sla_resolution_due_at->toDateTimeString());
    }

    private function invokeCalculateBusinessTime(Ticket $ticket, Carbon $start, int $minutes, TicketSlaPolicy $policy): Carbon
    {
        $method = new \ReflectionMethod($ticket, 'calculateBusinessTime');
        $method->setAccessible(true);

        return $method->invoke($ticket, $start, $minutes, $policy);
    }
}
