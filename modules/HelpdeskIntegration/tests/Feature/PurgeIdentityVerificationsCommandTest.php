<?php

namespace Modules\HelpdeskIntegration\Tests\Feature;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Tests\HelpdeskTestCase;
use Modules\HelpdeskIntegration\Models\CustomerIdentityVerification;

class PurgeIdentityVerificationsCommandTest extends HelpdeskTestCase
{
    private function makeVerification(Customer $customer, Carbon $createdAt): CustomerIdentityVerification
    {
        $verification = CustomerIdentityVerification::query()->create([
            'customer_id' => $customer->id,
            'channel' => 'email',
            'code_hash' => bcrypt('000000'),
            'expires_at' => $createdAt->copy()->addMinutes(10),
        ]);

        $verification->forceFill(['created_at' => $createdAt])->saveQuietly();

        return $verification;
    }

    public function test_deletes_only_verifications_older_than_retention(): void
    {
        $customer = Customer::factory()->create();

        $old = $this->makeVerification($customer, now()->subDays(45));
        $recent = $this->makeVerification($customer, now()->subDays(5));

        $this->artisan('helpdeskintegration:purge-identity-verifications', ['--days' => 30])
            ->assertExitCode(0);

        $this->assertDatabaseMissing('helpdesk_customer_identity_verifications', ['id' => $old->id], 'helpdesk');
        $this->assertDatabaseHas('helpdesk_customer_identity_verifications', ['id' => $recent->id], 'helpdesk');
    }

    public function test_dry_run_does_not_delete_anything(): void
    {
        $customer = Customer::factory()->create();
        $old = $this->makeVerification($customer, now()->subDays(45));

        $this->artisan('helpdeskintegration:purge-identity-verifications', ['--days' => 30, '--dry-run' => true])
            ->assertExitCode(0);

        $this->assertDatabaseHas('helpdesk_customer_identity_verifications', ['id' => $old->id], 'helpdesk');
    }

    public function test_rejects_non_positive_days(): void
    {
        $this->artisan('helpdeskintegration:purge-identity-verifications', ['--days' => 0])
            ->assertExitCode(Command::INVALID);
    }
}
