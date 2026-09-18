<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Mail\ScheduledReportMail;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * helpdesk:send-scheduled-reports (OFF por defecto): con el toggle apagado no
 * envía nada; activo, encola ScheduledReportMail (cola 'emails') a los
 * destinatarios configurados — o a los usuarios con permiso
 * helpdesk.metrics.view — con las métricas del periodo (TicketReportsService,
 * las mismas queries del dashboard) y el CSV opcional (TicketsExporter).
 */
class SendScheduledReportsCommandTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private Customer $customer;

    private TicketStatus $openStatus;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        config([
            'helpdesktickets.reports.scheduled.enabled' => false,
            'helpdesktickets.reports.scheduled.frequency' => 'weekly',
            'helpdesktickets.reports.scheduled.recipients' => '',
            'helpdesktickets.reports.scheduled.sections' => [
                'tickets' => true,
                'csat' => true,
                'ops' => true,
            ],
            'helpdesktickets.reports.scheduled.attach_csv' => true,
            'helpdesktickets.reports.scheduled.csv_max_rows' => 5000,
        ]);

        $this->openStatus = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $this->customer = Customer::firstOrCreate(
            ['email' => 'reports-customer@example.com'],
            ['name' => 'Reports Customer']
        );
    }

    private function makeTicket(array $overrides = []): Ticket
    {
        // created_at no es mass-assignable: se ajusta con forceFill como en
        // EscalateTicketsJobTest.
        $createdAt = $overrides['created_at'] ?? null;
        unset($overrides['created_at']);

        $ticket = Ticket::create(array_merge([
            'subject' => 'Scheduled report test ticket',
            'description' => 'Scheduled report test description',
            'customer_id' => $this->customer->id,
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'source' => 'portal',
        ], $overrides));

        if ($createdAt !== null) {
            $ticket->forceFill(['created_at' => $createdAt])->saveQuietly();
        }

        return $ticket;
    }

    public function test_command_sends_nothing_when_toggle_is_off(): void
    {
        config(['helpdesktickets.reports.scheduled.recipients' => 'boss@example.com']);

        $this->makeTicket();

        $this->artisan('helpdesk:send-scheduled-reports')->assertSuccessful();

        Mail::assertNotQueued(ScheduledReportMail::class);
        Mail::assertNothingQueued();
    }

    public function test_command_queues_mail_to_configured_recipients_when_enabled(): void
    {
        config([
            'helpdesktickets.reports.scheduled.enabled' => true,
            'helpdesktickets.reports.scheduled.recipients' => 'boss@example.com, ops@example.com',
        ]);

        $this->makeTicket();

        $this->artisan('helpdesk:send-scheduled-reports')->assertSuccessful();

        Mail::assertQueued(ScheduledReportMail::class, fn (ScheduledReportMail $mail) => $mail->hasTo('boss@example.com'));
        Mail::assertQueued(ScheduledReportMail::class, fn (ScheduledReportMail $mail) => $mail->hasTo('ops@example.com'));
        Mail::assertQueued(ScheduledReportMail::class, fn (ScheduledReportMail $mail) => $mail->queue === 'emails');
        Mail::assertQueuedCount(2);
    }

    public function test_command_falls_back_to_users_with_reports_permission(): void
    {
        config(['helpdesktickets.reports.scheduled.enabled' => true]);

        Permission::findOrCreate('helpdesk.metrics.view', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $manager = User::factory()->create();
        $manager->givePermissionTo('helpdesk.metrics.view');
        $outsider = User::factory()->create();

        $this->artisan('helpdesk:send-scheduled-reports')->assertSuccessful();

        Mail::assertQueued(ScheduledReportMail::class, fn (ScheduledReportMail $mail) => $mail->hasTo($manager->email));
        Mail::assertNotQueued(ScheduledReportMail::class, fn (ScheduledReportMail $mail) => $mail->hasTo($outsider->email));
    }

    public function test_report_contains_the_period_metrics(): void
    {
        config([
            'helpdesktickets.reports.scheduled.enabled' => true,
            'helpdesktickets.reports.scheduled.recipients' => 'boss@example.com',
        ]);

        // Datos mínimos del periodo (ventana semanal: últimos 7 días).
        $this->makeTicket(['created_at' => now()->subDays(2)]);
        $this->makeTicket([
            'created_at' => now()->subDays(2),
            'closed_at' => now()->subDay(),
            'sla_resolution_breached' => true,
        ]);

        // Los totales se calculan con las mismas queries del dashboard, así
        // que el esperado se lee de la BD con la misma ventana del comando.
        $from = now()->subDays(7)->startOfDay();
        $expectedCreated = Ticket::whereBetween('created_at', [$from, now()])->count();
        $expectedBreached = Ticket::whereBetween('created_at', [$from, now()])
            ->where('sla_resolution_breached', true)
            ->count();

        $this->artisan('helpdesk:send-scheduled-reports')->assertSuccessful();

        Mail::assertQueued(ScheduledReportMail::class, function (ScheduledReportMail $mail) use ($expectedCreated, $expectedBreached) {
            $cell = fn (string $label, int $value) => str_contains($mail->emailContent, e($label))
                && str_contains($mail->emailContent, '>'.$value.'</td>');

            return str_contains($mail->emailSubject, 'Informe semanal')
                && str_contains($mail->emailContent, 'Resumen de tickets')
                && str_contains($mail->emailContent, 'CSAT y valoraciones')
                && str_contains($mail->emailContent, 'Salud operativa')
                && $cell('Tickets creados', $expectedCreated)
                && $cell('SLA incumplidos', $expectedBreached);
        });
    }

    public function test_report_attaches_csv_with_period_tickets(): void
    {
        config([
            'helpdesktickets.reports.scheduled.enabled' => true,
            'helpdesktickets.reports.scheduled.recipients' => 'boss@example.com',
        ]);

        $ticket = $this->makeTicket(['created_at' => now()->subDays(2)]);

        $this->artisan('helpdesk:send-scheduled-reports')->assertSuccessful();

        Mail::assertQueued(ScheduledReportMail::class, function (ScheduledReportMail $mail) use ($ticket) {
            return $mail->csvContent !== null
                && str_contains($mail->csvContent, 'Ticket#')
                && str_contains($mail->csvContent, $ticket->ticket_number)
                && str_ends_with((string) $mail->csvFilename, '.csv');
        });
    }

    public function test_csv_attachment_can_be_disabled(): void
    {
        config([
            'helpdesktickets.reports.scheduled.enabled' => true,
            'helpdesktickets.reports.scheduled.recipients' => 'boss@example.com',
            'helpdesktickets.reports.scheduled.attach_csv' => false,
        ]);

        $this->makeTicket(['created_at' => now()->subDays(2)]);

        $this->artisan('helpdesk:send-scheduled-reports')->assertSuccessful();

        Mail::assertQueued(ScheduledReportMail::class, fn (ScheduledReportMail $mail) => $mail->csvContent === null);
    }
}
