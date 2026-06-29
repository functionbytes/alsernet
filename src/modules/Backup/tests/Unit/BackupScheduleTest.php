<?php

namespace Modules\Backup\Tests\Unit;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Backup\Models\BackupSchedule;
use Tests\TestCase;

class BackupScheduleTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // shouldRunNow — disabled
    // =========================================================================

    public function test_should_run_now_returns_false_when_disabled(): void
    {
        $schedule = BackupSchedule::make([
            'enabled' => false,
            'frequency' => 'daily',
            'scheduled_time' => Carbon::now()->format('H:i:s'),
            'backup_types' => ['config'],
        ]);

        $this->assertFalse($schedule->shouldRunNow());
    }

    // =========================================================================
    // shouldRunNow — daily
    // =========================================================================

    public function test_daily_schedule_runs_at_scheduled_time(): void
    {
        $schedule = BackupSchedule::make([
            'enabled' => true,
            'frequency' => 'daily',
            'scheduled_time' => Carbon::now()->format('H:i:s'),
            'backup_types' => ['config'],
        ]);

        $this->assertTrue($schedule->shouldRunNow());
    }

    public function test_daily_schedule_does_not_run_at_wrong_time(): void
    {
        $schedule = BackupSchedule::make([
            'enabled' => true,
            'frequency' => 'daily',
            'scheduled_time' => Carbon::now()->subHours(3)->format('H:i:s'),
            'backup_types' => ['config'],
        ]);

        $this->assertFalse($schedule->shouldRunNow());
    }

    // =========================================================================
    // shouldRunNow — weekly
    // =========================================================================

    public function test_weekly_schedule_runs_on_correct_day(): void
    {
        $schedule = BackupSchedule::make([
            'enabled' => true,
            'frequency' => 'weekly',
            'scheduled_time' => Carbon::now()->format('H:i:s'),
            'days_of_week' => [Carbon::now()->dayOfWeek],
            'backup_types' => ['config'],
        ]);

        $this->assertTrue($schedule->shouldRunNow());
    }

    public function test_weekly_schedule_skips_wrong_day(): void
    {
        // Use a day that is NOT today
        $wrongDay = (Carbon::now()->dayOfWeek + 3) % 7;

        $schedule = BackupSchedule::make([
            'enabled' => true,
            'frequency' => 'weekly',
            'scheduled_time' => Carbon::now()->format('H:i:s'),
            'days_of_week' => [$wrongDay],
            'backup_types' => ['config'],
        ]);

        $this->assertFalse($schedule->shouldRunNow());
    }

    // =========================================================================
    // shouldRunNow — monthly
    // =========================================================================

    public function test_monthly_schedule_runs_on_correct_day(): void
    {
        $schedule = BackupSchedule::make([
            'enabled' => true,
            'frequency' => 'monthly',
            'scheduled_time' => Carbon::now()->format('H:i:s'),
            'days_of_month' => [Carbon::now()->day],
            'backup_types' => ['config'],
        ]);

        $this->assertTrue($schedule->shouldRunNow());
    }

    // =========================================================================
    // shouldRunNow — custom
    // =========================================================================

    public function test_custom_schedule_runs_after_interval(): void
    {
        $schedule = BackupSchedule::make([
            'enabled' => true,
            'frequency' => 'custom',
            'scheduled_time' => Carbon::now()->format('H:i:s'),
            'custom_interval_hours' => 24,
            'last_run_at' => Carbon::now()->subHours(25),
            'backup_types' => ['config'],
        ]);

        $this->assertTrue($schedule->shouldRunNow());
    }

    public function test_custom_schedule_does_not_run_before_interval(): void
    {
        $schedule = BackupSchedule::make([
            'enabled' => true,
            'frequency' => 'custom',
            'scheduled_time' => Carbon::now()->format('H:i:s'),
            'custom_interval_hours' => 24,
            'last_run_at' => Carbon::now()->subHour(),
            'backup_types' => ['config'],
        ]);

        $this->assertFalse($schedule->shouldRunNow());
    }

    // =========================================================================
    // calculateNextRun — daily
    // =========================================================================

    public function test_calculate_next_run_daily_future(): void
    {
        $futureTime = Carbon::now()->addHour();

        $schedule = BackupSchedule::make([
            'enabled' => true,
            'frequency' => 'daily',
            'scheduled_time' => $futureTime->format('H:i:s'),
            'backup_types' => ['config'],
        ]);

        $nextRun = $schedule->calculateNextRun();

        $this->assertEquals($futureTime->format('H:i'), $nextRun->format('H:i'));
        $this->assertEquals(Carbon::now()->toDateString(), $nextRun->toDateString());
    }

    public function test_calculate_next_run_daily_past(): void
    {
        // Use a fixed time well in the past (midnight + 1 min) so it's always past
        $schedule = BackupSchedule::make([
            'enabled' => true,
            'frequency' => 'daily',
            'scheduled_time' => '00:01:00',
            'backup_types' => ['config'],
        ]);

        $nextRun = $schedule->calculateNextRun();

        $this->assertEquals(
            Carbon::now()->addDay()->toDateString(),
            $nextRun->toDateString()
        );
    }

    // =========================================================================
    // calculateNextRun — custom
    // =========================================================================

    public function test_calculate_next_run_custom(): void
    {
        $schedule = BackupSchedule::make([
            'enabled' => true,
            'frequency' => 'custom',
            'scheduled_time' => Carbon::now()->format('H:i:s'),
            'custom_interval_hours' => 6,
            'backup_types' => ['config'],
        ]);

        $nextRun = $schedule->calculateNextRun();

        // Should be approximately 6 hours from now (within 1 minute tolerance)
        $expectedTime = Carbon::now()->addHours(6);
        $this->assertEqualsWithDelta(
            $expectedTime->timestamp,
            $nextRun->timestamp,
            60
        );
    }

    // =========================================================================
    // markAsRun — requires DB
    // =========================================================================

    public function test_mark_as_run_updates_last_run_at(): void
    {
        $schedule = BackupSchedule::create([
            'name' => 'Test Schedule',
            'enabled' => true,
            'frequency' => 'daily',
            'scheduled_time' => '10:00:00',
            'backup_types' => ['config'],
        ]);

        $before = Carbon::now()->subSecond();

        $schedule->markAsRun();
        $schedule->refresh();

        $this->assertNotNull($schedule->last_run_at);
        $this->assertTrue($schedule->last_run_at->greaterThanOrEqualTo($before));
    }
}
