<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\HelpdeskTickets\Mail\OpsAlertMail;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Services\OpsHealthService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Observabilidad operativa del helpdesk: snapshot (helpdesk:ops-metrics),
 * exposición cacheada y alertas por umbral (OFF por defecto).
 */
class OpsHealthTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        Cache::forget(OpsHealthService::CACHE_KEY);
        Cache::forget('helpdesk:ops:alert-cooldown');

        config([
            'helpdesktickets.ops.queues' => [],
            'helpdesktickets.ops.alerts.enabled' => false,
            'helpdesktickets.ops.alerts.queue_depth' => 500,
            'helpdesktickets.ops.alerts.failed_jobs' => 0,
            'helpdesktickets.ops.alerts.sla_breaches_per_hour' => 10,
            'helpdesktickets.ops.alerts.cooldown_minutes' => 60,
        ]);
    }

    protected function tearDown(): void
    {
        Cache::forget(OpsHealthService::CACHE_KEY);
        Cache::forget('helpdesk:ops:alert-cooldown');

        parent::tearDown();
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

    private function createManager(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('manage_helpdesk', 'web');

        $user = User::factory()->create();
        $user->givePermissionTo('manage_helpdesk');

        return $user;
    }

    /**
     * Snapshot inflado para forzar umbrales sin depender del estado real de
     * colas/failed_jobs de la máquina.
     */
    private function unhealthySnapshot(): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'queues' => ['helpdesk' => 1000],
            'queue_total' => 1000,
            'failed_jobs' => 5,
            'webhooks_failing' => 0,
            'webhook_failed_deliveries_last_hour' => 0,
            'sla_breaches_last_hour' => 25,
            'unassigned_sla_warning' => 3,
            'ai_today' => null,
        ];
    }

    public function test_snapshot_contains_expected_keys(): void
    {
        $snapshot = app(OpsHealthService::class)->snapshot();

        foreach ([
            'generated_at',
            'queues',
            'queue_total',
            'failed_jobs',
            'webhooks_failing',
            'webhook_failed_deliveries_last_hour',
            'sla_breaches_last_hour',
            'unassigned_sla_warning',
            'ai_today',
        ] as $key) {
            $this->assertArrayHasKey($key, $snapshot);
        }

        $this->assertIsArray($snapshot['queues']);
        $this->assertIsInt($snapshot['queue_total']);
        $this->assertIsInt($snapshot['unassigned_sla_warning']);
    }

    public function test_unassigned_tickets_near_sla_breach_are_counted(): void
    {
        config(['helpdesktickets.ops.sla_warning_minutes' => 60]);

        $baseline = app(OpsHealthService::class)->snapshot()['unassigned_sla_warning'];

        Ticket::factory()->create([
            'assignee_id' => null,
            'closed_at' => null,
            'sla_first_response_due_at' => now()->addMinutes(30),
        ]);

        $snapshot = app(OpsHealthService::class)->snapshot();

        $this->assertSame($baseline + 1, $snapshot['unassigned_sla_warning']);
    }

    public function test_evaluate_alerts_returns_empty_below_thresholds(): void
    {
        $healthy = [
            'queues' => ['helpdesk' => 3],
            'queue_total' => 3,
            'failed_jobs' => 0,
            'sla_breaches_last_hour' => 0,
        ];

        $this->assertSame([], app(OpsHealthService::class)->evaluateAlerts($healthy));
    }

    public function test_evaluate_alerts_flags_each_exceeded_threshold(): void
    {
        $reasons = app(OpsHealthService::class)->evaluateAlerts($this->unhealthySnapshot());

        $this->assertCount(3, $reasons);
        $this->assertStringContainsString("Cola 'helpdesk'", $reasons[0]);
        $this->assertStringContainsString('dead-letter', $reasons[1]);
        $this->assertStringContainsString('SLA', $reasons[2]);
    }

    public function test_command_refreshes_cached_snapshot_and_sends_no_mail_by_default(): void
    {
        Mail::fake();

        $this->artisan('helpdesk:ops-metrics')->assertExitCode(0);

        $this->assertTrue(Cache::has(OpsHealthService::CACHE_KEY));
        $this->assertArrayHasKey('queue_total', Cache::get(OpsHealthService::CACHE_KEY));
        Mail::assertNothingQueued();
    }

    public function test_alerts_disabled_by_default_even_when_thresholds_exceeded(): void
    {
        Mail::fake();
        $this->createManager();

        $this->partialMock(OpsHealthService::class, function ($mock) {
            $mock->shouldReceive('refresh')->andReturn($this->unhealthySnapshot());
        });

        $this->artisan('helpdesk:ops-metrics')->assertExitCode(0);

        Mail::assertNothingQueued();
    }

    public function test_alert_mail_is_queued_to_managers_when_enabled_and_threshold_exceeded(): void
    {
        Mail::fake();
        config(['helpdesktickets.ops.alerts.enabled' => true]);

        $manager = $this->createManager();
        User::factory()->create(); // usuario sin permiso: no debe recibir mail

        $this->partialMock(OpsHealthService::class, function ($mock) {
            $mock->shouldReceive('refresh')->andReturn($this->unhealthySnapshot());
        });

        $this->artisan('helpdesk:ops-metrics')->assertExitCode(0);

        Mail::assertQueued(OpsAlertMail::class, 1);
        Mail::assertQueued(OpsAlertMail::class, function (OpsAlertMail $mail) use ($manager) {
            return $mail->hasTo($manager->email)
                && str_contains($mail->emailContent, 'dead-letter');
        });
    }

    public function test_alert_cooldown_prevents_repeated_mail(): void
    {
        Mail::fake();
        config(['helpdesktickets.ops.alerts.enabled' => true]);
        $this->createManager();

        $this->partialMock(OpsHealthService::class, function ($mock) {
            $mock->shouldReceive('refresh')->twice()->andReturn($this->unhealthySnapshot());
        });

        $this->artisan('helpdesk:ops-metrics')->assertExitCode(0);
        $this->artisan('helpdesk:ops-metrics')->assertExitCode(0);

        Mail::assertQueued(OpsAlertMail::class, 1);
    }

    public function test_no_alerts_option_skips_alert_evaluation(): void
    {
        Mail::fake();
        config(['helpdesktickets.ops.alerts.enabled' => true]);
        $this->createManager();

        $this->partialMock(OpsHealthService::class, function ($mock) {
            $mock->shouldReceive('refresh')->andReturn($this->unhealthySnapshot());
        });

        $this->artisan('helpdesk:ops-metrics', ['--no-alerts' => true])->assertExitCode(0);

        Mail::assertNothingQueued();
    }
}
