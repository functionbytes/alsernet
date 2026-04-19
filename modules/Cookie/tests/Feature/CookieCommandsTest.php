<?php

namespace Modules\Cookie\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Modules\Cookie\Models\CookieConsentLog;
use Modules\Cookie\Notifications\LowConsentRateNotification;
use Modules\Cookie\Tests\TestCase;
use Spatie\Permission\Models\Permission;

class CookieCommandsTest extends TestCase
{
    // ───── Helpers ────────────────────────────────────────────────────────────

    private function makeLog(array $overrides = []): void
    {
        $attrs = array_merge([
            'session_id' => substr(md5(uniqid('', true)), 0, 40),
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'action' => 'accept_all',
            'accepted_categories' => json_encode([]),
            'user_agent' => 'TestBrowser/1.0',
            'version' => '1.0',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        // Use DB insert to allow custom created_at (Eloquent auto-sets timestamps)
        DB::table('cookie_consent_logs')->insert($attrs);
    }

    // ───── cookie:cleanup-logs ────────────────────────────────────────────────

    public function test_cleanup_logs_command_deletes_old_records(): void
    {
        $this->makeLog(['created_at' => now()->subDays(100)]);

        $this->artisan('cookie:cleanup-logs', ['--days' => 90])
            ->assertSuccessful();

        $this->assertSame(0, CookieConsentLog::count());
    }

    public function test_cleanup_logs_command_keeps_recent_records(): void
    {
        $this->makeLog(['created_at' => now()->subDays(30)]);

        $this->artisan('cookie:cleanup-logs', ['--days' => 90])
            ->assertSuccessful();

        $this->assertSame(1, CookieConsentLog::count());
    }

    public function test_cleanup_logs_command_uses_default_90_days(): void
    {
        // One record older than 90 days, one within the window
        $this->makeLog(['created_at' => now()->subDays(91)]);
        $this->makeLog(['created_at' => now()->subDays(30)]);

        $this->artisan('cookie:cleanup-logs')
            ->assertSuccessful();

        // Only the 91-day-old record should be deleted; the 30-day one stays
        $this->assertSame(1, CookieConsentLog::count());
    }

    // ───── cookie:check-consent-rate ─────────────────────────────────────────

    public function test_check_consent_rate_does_nothing_with_insufficient_data(): void
    {
        Notification::fake();

        // Fewer than 10 records in the last 24h
        foreach (range(1, 5) as $i) {
            $this->makeLog(['action' => 'accept_all', 'session_id' => str_repeat((string) $i, 40)]);
        }

        $this->artisan('cookie:check-consent-rate')
            ->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_check_consent_rate_notifies_when_below_threshold(): void
    {
        Notification::fake();

        // alert_threshold defaults to 30 in the command; no Setting override needed

        // Create an admin user with the required permission
        Permission::firstOrCreate(['name' => 'cookie.settings.view', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->givePermissionTo('cookie.settings.view');

        // 20% acceptance rate: 2 accept_all out of 10 total
        foreach (range(1, 2) as $i) {
            $this->makeLog(['action' => 'accept_all', 'session_id' => str_pad((string) $i, 40, 'a')]);
        }
        foreach (range(3, 10) as $i) {
            $this->makeLog(['action' => 'reject_all', 'session_id' => str_pad((string) $i, 40, 'a')]);
        }

        $this->artisan('cookie:check-consent-rate')
            ->assertSuccessful();

        Notification::assertSentTo($admin, LowConsentRateNotification::class);
    }
}
