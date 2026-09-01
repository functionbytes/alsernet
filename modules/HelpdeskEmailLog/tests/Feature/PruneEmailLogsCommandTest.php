<?php

namespace Modules\HelpdeskEmailLog\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskEmailLog\Enums\EmailStatus;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Tests\TestCase;

class PruneEmailLogsCommandTest extends TestCase
{
    use DatabaseTransactions;

    // 'mysql' imprescindible: EmailLog vive en la conexión default de la app
    // (mysql en este entorno, no mariadb/helpdesk) — mismo gotcha ya
    // documentado y corregido en EmailLogControllerTest/EmailOpenTrackingTest.
    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    protected function setUp(): void
    {
        parent::setUp();

        // phpunit.xml fuerza MAIL_MAILER=array (force="true"), pero dentro de
        // Docker getenv()/env() ignoran ese force y siguen devolviendo 'smtp'
        // real, así que config('mail.default') caía en 'smtp'. Este archivo no
        // envía correo directamente, pero se deja por consistencia con el resto
        // del módulo (ver EmailTrackingTest) — es inocuo si no aplica.
        config(['mail.default' => 'array']);
    }

    public function test_old_entries_are_deleted_after_retention_window(): void
    {
        // --days/--stale-hours ganan sobre Setting::get() (ver PruneEmailLogsCommand
        // líneas 29/51: `$this->option(...) ?? Setting::get(...)`) — se pasan
        // explícitos en vez de config()->set(), que Setting::get() ignora por
        // completo cuando ya existe una fila real en la tabla settings (hay una:
        // retention_days=90/stale_queued_hours=24 reales en este entorno
        // compartido). Mismo criterio que ya usaba con éxito
        // test_command_respects_option_overrides — se generaliza a los demás.
        $old = EmailLog::factory()->create(['created_at' => now()->subDays(45)]);
        $fresh = EmailLog::factory()->create(['created_at' => now()->subDays(5)]);

        $this->artisan('email-logs:prune', ['--days' => 30, '--stale-hours' => 0])->assertSuccessful();

        $this->assertDatabaseMissing('email_logs', ['id' => $old->id]);
        $this->assertDatabaseHas('email_logs', ['id' => $fresh->id]);
    }

    public function test_retention_is_skipped_when_days_is_zero(): void
    {
        $old = EmailLog::factory()->create(['created_at' => now()->subYears(2)]);

        $this->artisan('email-logs:prune', ['--days' => 0, '--stale-hours' => 0])->assertSuccessful();

        $this->assertDatabaseHas('email_logs', ['id' => $old->id]);
    }

    public function test_stale_queued_entries_are_marked_as_failed(): void
    {
        $stale = EmailLog::factory()->queued()->create([
            'created_at' => now()->subHours(12),
        ]);
        $fresh = EmailLog::factory()->queued()->create([
            'created_at' => now()->subHour(),
        ]);

        $this->artisan('email-logs:prune', ['--days' => 0, '--stale-hours' => 6])->assertSuccessful();

        $this->assertSame(EmailStatus::Failed, $stale->fresh()->status);
        $this->assertNotNull($stale->fresh()->failed_at);
        $this->assertSame(EmailStatus::Queued, $fresh->fresh()->status);
    }

    public function test_command_respects_option_overrides(): void
    {
        config()->set('helpdeskemaillog.retention_days', 365);
        config()->set('helpdeskemaillog.stale_queued_hours', 168);

        $old = EmailLog::factory()->create(['created_at' => now()->subDays(50)]);

        $this->artisan('email-logs:prune', ['--days' => 30])->assertSuccessful();

        $this->assertDatabaseMissing('email_logs', ['id' => $old->id]);
    }
}
