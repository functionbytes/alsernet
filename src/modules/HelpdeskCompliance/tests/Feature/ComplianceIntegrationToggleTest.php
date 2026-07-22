<?php

namespace Modules\HelpdeskCompliance\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Events\CustomerGdprDeleted;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskCompliance\Listeners\RunComplianceCascade;
use Tests\TestCase;

/**
 * Regression coverage for the `compliance.integration_enabled` admin toggle
 * (panel/settings/helpdesk/integrations). The core GDPR deletion always runs
 * before this listener fires; this only verifies the additional Helpdesk
 * cascade (ComplianceRequest tracking + Tickets/ChatFlow handlers) is skipped
 * while the toggle is disabled.
 */
class ComplianceIntegrationToggleTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = [null, 'helpdesk'];

    protected function tearDown(): void
    {
        Setting::set('compliance.integration_enabled', '1', 'integrations');

        parent::tearDown();
    }

    public function test_cascade_listener_skips_when_toggle_is_disabled(): void
    {
        Setting::set('compliance.integration_enabled', '0', 'integrations');

        $customer = Customer::factory()->create();
        $event = new CustomerGdprDeleted($customer, false, [], ['deleted' => 0, 'anonymized' => 1]);

        (new RunComplianceCascade)->handle($event);

        $this->assertDatabaseMissing('helpdesk_compliance_requests', [
            'customer_id' => $customer->id,
        ], 'helpdesk');
    }

    public function test_cascade_listener_records_request_when_toggle_is_enabled(): void
    {
        Setting::set('compliance.integration_enabled', '1', 'integrations');

        $customer = Customer::factory()->create();
        $event = new CustomerGdprDeleted($customer, false, [], ['deleted' => 0, 'anonymized' => 1]);

        (new RunComplianceCascade)->handle($event);

        $this->assertDatabaseHas('helpdesk_compliance_requests', [
            'customer_id' => $customer->id,
            'type' => 'delete_soft',
            'status' => 'completed',
        ], 'helpdesk');
    }
}
