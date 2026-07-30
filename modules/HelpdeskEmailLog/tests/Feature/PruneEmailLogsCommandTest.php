<?php

namespace Modules\HelpdeskEmailLog\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskEmailLog\Enums\EmailStatus;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Tests\TestCase;

class PruneEmailLogsCommandTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    public function test_old_entries_are_deleted_after_retention_window(): void
    {
        config()->set('helpdeskemaillog.retention_days', 30);
        config()->set('helpdeskemaillog.stale_queued_hours', 0);

        $old = EmailLog::factory()->create(['created_at' => now()->subDays(45)]);
        $fresh = EmailLog::factory()->create(['created_at' => now()->subDays(5)]);

        $this->artisan('email-logs:prune')->assertSuccessful();

        $this->assertDatabaseMissing('email_logs', ['id' => $old->id]);
        $this->assertDatabaseHas('email_logs', ['id' => $fresh->id]);
    }

    public function test_retention_is_skipped_when_days_is_zero(): void
    {
        config()->set('helpdeskemaillog.retention_days', 0);
        config()->set('helpdeskemaillog.stale_queued_hours', 0);

        $old = EmailLog::factory()->create(['created_at' => now()->subYears(2)]);

        $this->artisan('email-logs:prune')->assertSuccessful();

        $this->assertDatabaseHas('email_logs', ['id' => $old->id]);
    }

    public function test_stale_queued_entries_are_marked_as_failed(): void
    {
        config()->set('helpdeskemaillog.retention_days', 0);
        config()->set('helpdeskemaillog.stale_queued_hours', 6);

        $stale = EmailLog::factory()->queued()->create([
            'created_at' => now()->subHours(12),
        ]);
        $fresh = EmailLog::factory()->queued()->create([
            'created_at' => now()->subHour(),
        ]);

        $this->artisan('email-logs:prune')->assertSuccessful();

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
