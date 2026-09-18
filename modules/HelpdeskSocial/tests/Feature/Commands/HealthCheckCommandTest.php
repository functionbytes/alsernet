<?php

namespace Modules\HelpdeskSocial\Tests\Feature\Commands;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Modules\HelpdeskSocial\Models\SocialAccount;
use Modules\HelpdeskSocial\Tests\TestCase;
use Spatie\Permission\Models\Role;

class HealthCheckCommandTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        // Create roles for notifications
        Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        User::factory()->create()->assignRole($superAdmin);
    }

    public function test_health_check_reports_no_issues_when_all_healthy(): void
    {
        SocialAccount::factory()->create([
            'is_active' => true,
            'consecutive_failures' => 0,
            'token_expires_at' => now()->addDays(30),
            'last_synced_at' => now(),
        ]);

        $this->artisan('helpdesk-social:health-check --notify')
            ->assertSuccessful();
    }

    public function test_health_check_detects_inactive_account(): void
    {
        SocialAccount::factory()->create([
            'is_active' => false,
            'consecutive_failures' => 5,
            'circuit_breaker_active' => true,
            'last_synced_at' => now(),
        ]);

        $this->artisan('helpdesk-social:health-check --notify')
            ->assertSuccessful();
    }

    public function test_health_check_detects_expiring_token(): void
    {
        SocialAccount::factory()->create([
            'is_active' => true,
            'token_expires_at' => now()->addDays(3),
        ]);

        $this->artisan('helpdesk-social:health-check --notify')
            ->assertSuccessful();
    }
}
