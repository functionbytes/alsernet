<?php

namespace Modules\HelpdeskIntegration\Tests\Feature;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Tests\HelpdeskTestCase;
use Modules\HelpdeskIntegration\Models\IntegrationAuditLog;

class PurgeIntegrationAuditLogCommandTest extends HelpdeskTestCase
{
    private function makeEntry(Customer $customer, Carbon $createdAt): IntegrationAuditLog
    {
        $entry = IntegrationAuditLog::query()->create([
            'customer_id' => $customer->id,
            'platform' => 'prestashop',
            'action' => 'linked',
            'external_id' => '4242',
            'user_id' => $this->manager->id,
        ]);

        $entry->forceFill(['created_at' => $createdAt])->saveQuietly();

        return $entry;
    }

    public function test_deletes_only_entries_older_than_retention(): void
    {
        $customer = Customer::factory()->create();

        $old = $this->makeEntry($customer, now()->subDays(200));
        $recent = $this->makeEntry($customer, now()->subDays(10));

        $this->artisan('helpdeskintegration:purge-audit-log', ['--days' => 180])
            ->assertExitCode(0);

        $this->assertDatabaseMissing('helpdesk_integration_audit_log', ['id' => $old->id], 'helpdesk');
        $this->assertDatabaseHas('helpdesk_integration_audit_log', ['id' => $recent->id], 'helpdesk');
    }

    public function test_dry_run_does_not_delete_anything(): void
    {
        $customer = Customer::factory()->create();
        $old = $this->makeEntry($customer, now()->subDays(200));

        $this->artisan('helpdeskintegration:purge-audit-log', ['--days' => 180, '--dry-run' => true])
            ->assertExitCode(0);

        $this->assertDatabaseHas('helpdesk_integration_audit_log', ['id' => $old->id], 'helpdesk');
    }

    public function test_rejects_non_positive_days(): void
    {
        $this->artisan('helpdeskintegration:purge-audit-log', ['--days' => -1])
            ->assertExitCode(Command::INVALID);
    }
}
