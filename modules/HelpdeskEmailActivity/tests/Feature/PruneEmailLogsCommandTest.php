<?php

namespace Modules\HelpdeskEmailActivity\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskEmailActivity\Enums\EmailStatus;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
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
        config()->set('helpdeskemailactivity.retention_days', 365);
        config()->set('helpdeskemailactivity.stale_queued_hours', 168);

        $old = EmailLog::factory()->create(['created_at' => now()->subDays(50)]);

        $this->artisan('email-logs:prune', ['--days' => 30])->assertSuccessful();

        $this->assertDatabaseMissing('email_logs', ['id' => $old->id]);
    }

    /**
     * Papelera (30 días de recuperación, ver EmailLog::class/SoftDeletes):
     * un registro que un agente borró desde el panel (destroy()) hace más
     * de trash_retention_days días se purga de verdad (forceDelete); uno
     * borrado hace poco sigue recuperable.
     */
    public function test_trash_entries_are_purged_after_the_trash_retention_window(): void
    {
        $recentlyTrashed = EmailLog::factory()->create();
        $recentlyTrashed->delete();

        $oldTrashed = EmailLog::factory()->create();
        $oldTrashed->delete();
        // Simula que el borrado ocurrió hace 45 días — save() en la propia
        // instancia no pasa por el scope global de SoftDeletes (ver
        // Model::newModelQuery()), así que puede escribir deleted_at
        // libremente aunque la fila ya esté "oculta".
        $oldTrashed->forceFill(['deleted_at' => now()->subDays(45)])->save();

        // --days=0/--stale-hours=0: aísla esta purga de deleteOldEntries()/
        // markStaleQueuedAsFailed(), que no son lo que se está probando aquí.
        $this->artisan('email-logs:prune', ['--days' => 0, '--stale-hours' => 0, '--trash-days' => 30])
            ->assertSuccessful();

        $this->assertDatabaseMissing('email_logs', ['id' => $oldTrashed->id]);
        $this->assertDatabaseHas('email_logs', ['id' => $recentlyTrashed->id]);
    }

    public function test_trash_purge_is_skipped_when_trash_days_is_zero(): void
    {
        $oldTrashed = EmailLog::factory()->create();
        $oldTrashed->delete();
        $oldTrashed->forceFill(['deleted_at' => now()->subDays(90)])->save();

        $this->artisan('email-logs:prune', ['--days' => 0, '--stale-hours' => 0, '--trash-days' => 0])
            ->assertSuccessful();

        $this->assertDatabaseHas('email_logs', ['id' => $oldTrashed->id]);
    }

    /**
     * DECISIÓN (ver PruneEmailLogsCommand::deleteOldEntries()):
     * retention_days es el límite superior de TODO el histórico, papelera
     * incluida — una fila que un agente ya movió a la papelera pero que por
     * su created_at ya superó la retención general se purga de verdad aquí,
     * sin esperar a que además cumpla su propia ventana de 30 días de
     * papelera.
     */
    public function test_general_retention_also_purges_already_trashed_entries_past_retention(): void
    {
        $old = EmailLog::factory()->create(['created_at' => now()->subDays(120)]);
        $old->delete(); // deleted_at = ahora mismo, muy lejos de cumplir 30 días.

        // --trash-days=0: aísla esta purga de pruneTrash(), que no es lo que
        // se está probando aquí.
        $this->artisan('email-logs:prune', ['--days' => 90, '--stale-hours' => 0, '--trash-days' => 0])
            ->assertSuccessful();

        $this->assertDatabaseMissing('email_logs', ['id' => $old->id]);
    }
}
