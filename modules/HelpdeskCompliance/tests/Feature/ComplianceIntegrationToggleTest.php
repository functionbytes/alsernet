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
 * (panel/settings/helpdesk/integrations).
 *
 * SEC-05 item 1: este toggle SOLO gatea navegación/UI (ver registerNav() en
 * HelpdeskComplianceServiceProvider) — nunca la ejecución de la cascada legal
 * de borrado GDPR. Antes, con el toggle apagado, el listener retornaba sin
 * encolar nada: el borrado core (irreversible) se aplicaba igualmente pero
 * tickets/chatflow/email logs quedaban con PII intacta y SIN rastro en el
 * audit trail. Estos tests verifican que el toggle, en cualquier estado, deja
 * la cascada intacta.
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

    public function test_cascade_listener_creates_the_pending_request_when_toggle_is_disabled(): void
    {
        Setting::set('compliance.integration_enabled', '0', 'integrations');

        $customer = Customer::factory()->create();
        $event = new CustomerGdprDeleted($customer, false, [], ['deleted' => 0, 'anonymized' => 1]);

        (new RunComplianceCascade)->handle($event);

        $this->assertDatabaseHas('helpdesk_compliance_requests', [
            'customer_id' => $customer->id,
            'type' => 'delete_soft',
            'status' => 'pending',
        ], 'helpdesk');
    }

    public function test_cascade_listener_creates_the_pending_request_when_toggle_is_enabled(): void
    {
        Setting::set('compliance.integration_enabled', '1', 'integrations');

        $customer = Customer::factory()->create();
        $event = new CustomerGdprDeleted($customer, false, [], ['deleted' => 0, 'anonymized' => 1]);

        (new RunComplianceCascade)->handle($event);

        $this->assertDatabaseHas('helpdesk_compliance_requests', [
            'customer_id' => $customer->id,
            'type' => 'delete_soft',
            'status' => 'pending',
        ], 'helpdesk');
    }
}
