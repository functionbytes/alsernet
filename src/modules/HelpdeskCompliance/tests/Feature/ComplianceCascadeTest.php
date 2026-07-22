<?php

namespace Modules\HelpdeskCompliance\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Modules\Helpdesk\Events\CustomerGdprDeleted;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\Compliance\GdprDeletionService;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use Modules\HelpdeskCompliance\Jobs\ProcessComplianceCascadeJob;
use Modules\HelpdeskCompliance\Listeners\RunComplianceCascade;
use Modules\HelpdeskCompliance\Models\ComplianceRequest;
use Modules\HelpdeskTickets\Models\Ticket;
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

    /**
     * Envuelve tambien la conexion 'helpdesk' (donde viven tickets, sesiones,
     * compliance_requests...): sin esto los datos de test persisten entre runs
     * y las aserciones — que deben leer por la MISMA conexion — ven snapshots
     * obsoletos de la conexion por defecto.
     */
    protected $connectionsToTransact = [null, 'helpdesk'];

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

    // ─── el listener encola, no ejecuta en la request ──────────────────────────

    public function test_cascade_listener_queues_the_job_instead_of_running_inline(): void
    {
        Queue::fake();

        $customer = Customer::factory()->create();
        $event = new CustomerGdprDeleted($customer, false, [7, 8], ['deleted' => 0, 'anonymized' => 1]);

        (new RunComplianceCascade)->handle($event);

        Queue::assertPushedOn('helpdeskcompliance', ProcessComplianceCascadeJob::class);

        // No debe haber corrido nada de forma sincrona en la request.
        $this->assertDatabaseMissing('helpdesk_compliance_requests', ['customer_id' => $customer->id], 'helpdesk');
    }

    public function test_hard_deletion_is_queued_with_hard_flag(): void
    {
        Queue::fake();

        $customer = Customer::factory()->create();
        $event = new CustomerGdprDeleted($customer, true, [], ['deleted' => 3, 'anonymized' => 0]);

        (new RunComplianceCascade)->handle($event);

        Queue::assertPushed(function (ProcessComplianceCascadeJob $job) {
            $ref = new \ReflectionObject($job);
            $hard = $ref->getProperty('hard');
            $hard->setAccessible(true);

            return $hard->getValue($job) === true;
        });
    }

    // ─── el job realmente ejecuta la cascada (borrado real, no solo el evento) ─

    public function test_job_records_a_compliance_request_on_completion(): void
    {
        $customer = Customer::factory()->create();

        (new ProcessComplianceCascadeJob($customer->id, false, [], ['deleted' => 0, 'anonymized' => 1], null))->handle();

        $this->assertDatabaseHas('helpdesk_compliance_requests', [
            'customer_id' => $customer->id,
            'type' => 'delete_soft',
            'status' => 'completed',
        ], 'helpdesk');
    }

    public function test_job_hard_mode_is_recorded_as_delete_hard(): void
    {
        $customer = Customer::factory()->create();

        (new ProcessComplianceCascadeJob($customer->id, true, [], ['deleted' => 3, 'anonymized' => 0], null))->handle();

        $this->assertDatabaseHas('helpdesk_compliance_requests', [
            'customer_id' => $customer->id,
            'type' => 'delete_hard',
        ], 'helpdesk');
    }

    /**
     * Regresión: un fallo de la cascada GDPR debe quedar en el audit trail
     * (gdpr.cascade.failed), no solo en Log::error — antes failed() solo logeaba.
     */
    public function test_job_failure_records_a_compliance_request_and_audit_log(): void
    {
        $customer = Customer::factory()->create();

        (new ProcessComplianceCascadeJob($customer->id, false, [], ['deleted' => 0, 'anonymized' => 0], null))
            ->failed(new \RuntimeException('boom'));

        $this->assertDatabaseHas('helpdesk_compliance_requests', [
            'customer_id' => $customer->id,
            'type' => 'delete_soft',
            'status' => 'failed',
        ], 'helpdesk');

        $this->assertDatabaseHas('helpdesk_audit_logs', [
            'action' => 'gdpr.cascade.failed',
            'entity_type' => (new ComplianceRequest)->getMorphClass(),
        ], 'helpdesk');
    }

    /**
     * El hallazgo de la auditoria: nada probaba que forceDelete() REALMENTE
     * borra los tickets — una regresion futura (ej. cambiar forceDelete() por
     * delete()) pasaria desapercibida pese a ser borrado legalmente exigido.
     */
    public function test_job_hard_mode_permanently_deletes_tickets(): void
    {
        $customer = Customer::factory()->create();
        // ticket_number explícito y único: el generador global (TCK-YYYY-#####)
        // colisiona cuando otra suite corre en paralelo contra la misma BD.
        $ticket = Ticket::factory()->create(['customer_id' => $customer->id, 'ticket_number' => 'TCK-TEST-'.uniqid()]);

        (new ProcessComplianceCascadeJob($customer->id, true, [], ['deleted' => 1, 'anonymized' => 0], null))->handle();

        $this->assertDatabaseMissing('helpdesk_tickets', ['id' => $ticket->id], 'helpdesk');
    }

    public function test_job_soft_mode_redacts_and_soft_deletes_tickets(): void
    {
        $customer = Customer::factory()->create();
        $ticket = Ticket::factory()->create(['customer_id' => $customer->id, 'description' => 'Datos personales sensibles', 'ticket_number' => 'TCK-TEST-'.uniqid()]);

        (new ProcessComplianceCascadeJob($customer->id, false, [], ['deleted' => 0, 'anonymized' => 1], null))->handle();

        $this->assertDatabaseHas('helpdesk_tickets', [
            'id' => $ticket->id,
            'description' => config('helpdeskcompliance.redacted_text'),
        ], 'helpdesk');
        $this->assertSoftDeleted('helpdesk_tickets', ['id' => $ticket->id], 'helpdesk');
    }

    public function test_job_hard_mode_permanently_deletes_chatflow_sessions(): void
    {
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);
        $flow = ChatFlow::factory()->create();

        $session = ChatFlowSession::create([
            'chat_flow_id' => $flow->id,
            'conversation_id' => $conversation->id,
            'status' => 'completed',
            'context' => ['pii' => 'dato sensible'],
            'trigger_type' => 'manual',
            'started_at' => now(),
        ]);

        (new ProcessComplianceCascadeJob($customer->id, true, [$conversation->id], ['deleted' => 1, 'anonymized' => 0], null))->handle();

        $this->assertDatabaseMissing('helpdesk_chat_flow_sessions', ['id' => $session->id], 'helpdesk');
    }

    public function test_job_soft_mode_sanitizes_chatflow_session_context(): void
    {
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);
        $flow = ChatFlow::factory()->create();

        $session = ChatFlowSession::create([
            'chat_flow_id' => $flow->id,
            'conversation_id' => $conversation->id,
            'status' => 'completed',
            'context' => ['pii' => 'dato sensible'],
            'trigger_type' => 'manual',
            'started_at' => now(),
        ]);

        (new ProcessComplianceCascadeJob($customer->id, false, [$conversation->id], ['deleted' => 0, 'anonymized' => 1], null))->handle();

        $this->assertDatabaseHas('helpdesk_chat_flow_sessions', ['id' => $session->id, 'context' => null], 'helpdesk');
    }
}
