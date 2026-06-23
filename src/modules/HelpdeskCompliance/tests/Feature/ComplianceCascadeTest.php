<?php

namespace Modules\HelpdeskCompliance\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Modules\Helpdesk\Events\CustomerGdprDeleted;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\Compliance\GdprDeletionService;
use Modules\HelpdeskCompliance\Listeners\RunComplianceCascade;
use Tests\TestCase;

/**
 * Feature tests for the GDPR compliance cascade.
 *
 * NOTE: requires the helpdesk connection with the compliance_requests table
 * migrated. The shared test snapshot is currently blocked, so these are verified
 * statically (php -l + pint) and via tinker, and will run once the test DB is rebuilt.
 */
class ComplianceCascadeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_gdpr_deletion_dispatches_the_cascade_event(): void
    {
        Event::fake([CustomerGdprDeleted::class]);

        $customer = Customer::factory()->create();

        app(GdprDeletionService::class)->deleteCustomer($customer, false);

        Event::assertDispatched(
            CustomerGdprDeleted::class,
            fn (CustomerGdprDeleted $event): bool => $event->customer->id === $customer->id && $event->hard === false
        );
    }

    public function test_cascade_listener_records_a_compliance_request(): void
    {
        $customer = Customer::factory()->create();

        $event = new CustomerGdprDeleted($customer, false, [], ['deleted' => 0, 'anonymized' => 1]);

        (new RunComplianceCascade)->handle($event);

        $this->assertDatabaseHas('helpdesk_compliance_requests', [
            'customer_id' => $customer->id,
            'type' => 'delete_soft',
            'status' => 'completed',
        ]);
    }

    public function test_hard_deletion_is_recorded_as_delete_hard(): void
    {
        $customer = Customer::factory()->create();

        $event = new CustomerGdprDeleted($customer, true, [], ['deleted' => 3, 'anonymized' => 0]);

        (new RunComplianceCascade)->handle($event);

        $this->assertDatabaseHas('helpdesk_compliance_requests', [
            'customer_id' => $customer->id,
            'type' => 'delete_hard',
        ]);
    }
}
