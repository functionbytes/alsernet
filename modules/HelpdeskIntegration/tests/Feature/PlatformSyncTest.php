<?php

namespace Modules\HelpdeskIntegration\Tests\Feature;

use App\Models\User;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Tests\HelpdeskTestCase;
use Modules\HelpdeskIntegration\Database\Seeders\HelpdeskIntegrationProvidersSeeder;
use Modules\HelpdeskIntegration\Models\CustomerIdentityVerification;

class PlatformSyncTest extends HelpdeskTestCase
{
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HelpdeskIntegrationProvidersSeeder::class);

        $this->customer = Customer::factory()->create(['email' => 'sync@example.com']);
        $this->customer->linkExternalId('prestashop', '4242');

        CustomerIdentityVerification::query()->create([
            'customer_id' => $this->customer->id,
            'channel' => 'email',
            'code_hash' => bcrypt('000000'),
            'expires_at' => now()->addMinutes(10),
            'verified_at' => now(),
            'verified_by' => $this->manager->id,
        ]);
    }

    public function test_guest_cannot_sync_single_platform(): void
    {
        $this->postJson(route('manager.helpdesk.customers.integrations.sync-platform', [$this->customer, 'prestashop']))
            ->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_sync_single_platform(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.customers.integrations.sync-platform', [$this->customer, 'prestashop']))
            ->assertForbidden();
    }

    public function test_manager_can_sync_a_single_connected_platform(): void
    {
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.integrations.sync-platform', [$this->customer, 'prestashop']))
            ->assertOk()
            ->assertJsonPath('success', true);

        $link = $this->customer->externalIds()->where('platform', 'prestashop')->first();
        $this->assertNotNull($link->last_synced_at);
        // Con driver real el resultado depende del entorno: ok (existe),
        // not_found (la plataforma respondió sin match) o error (caída).
        $this->assertContains($link->sync_status, ['ok', 'not_found', 'error']);
    }

    public function test_sync_platform_returns_404_when_not_linked(): void
    {
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.integrations.sync-platform', [$this->customer, 'erp']))
            ->assertNotFound();
    }

    public function test_sync_platform_writes_audit_entry(): void
    {
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.integrations.sync-platform', [$this->customer, 'prestashop']));

        $this->assertDatabaseHas('helpdesk_integration_audit_log', [
            'customer_id' => $this->customer->id,
            'platform' => 'prestashop',
            'action' => 'synced',
        ], 'helpdesk');
    }

    public function test_bulk_sync_only_touches_connected_platforms(): void
    {
        $resp = $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.integrations.sync', $this->customer))
            ->assertOk()
            ->assertJsonPath('linked', true)
            ->json('integrations');

        $erp = collect($resp)->firstWhere('platform', 'erp');
        $this->assertNull($erp['last_synced_at']);

        $ps = collect($resp)->firstWhere('platform', 'prestashop');
        $this->assertNotNull($ps['last_synced_at']);
    }

    public function test_sync_requires_identity_verification(): void
    {
        $unverified = Customer::factory()->create(['email' => 'noverify@example.com']);
        $unverified->linkExternalId('prestashop', '9999');

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.integrations.sync-platform', [$unverified, 'prestashop']))
            ->assertForbidden();
    }
}
