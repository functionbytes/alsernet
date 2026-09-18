<?php

namespace Modules\HelpdeskCompliance\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Modules\Helpdesk\Events\CustomerGdprDeleted;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Setting;
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

    /**
     * El job ya no crea el ComplianceRequest — lo crea RunComplianceCascade de
     * forma sincrona y le pasa el id. Este helper simula esa parte para los
     * tests que instancian el job directamente.
     */
    private function pendingRequestId(int $customerId, bool $hard): int
    {
        return ComplianceRequest::create([
            'customer_id' => $customerId,
            'type' => $hard ? ComplianceRequest::TYPE_DELETE_HARD : ComplianceRequest::TYPE_DELETE_SOFT,
            'status' => 'pending',
        ])->id;
    }

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
    }

    /**
     * Hallazgo de la auditoría (SEC-05, item 2): el ComplianceRequest debe
     * crearse SINCRONO en la request que dispara el borrado, en 'pending' —
     * no esperar a que el worker recoja el job. Antes no había NINGÚN rastro
     * hasta que el job terminaba.
     */
    public function test_cascade_listener_creates_a_pending_compliance_request_synchronously(): void
    {
        Queue::fake();

        $customer = Customer::factory()->create();
        $event = new CustomerGdprDeleted($customer, false, [], ['deleted' => 0, 'anonymized' => 1]);

        (new RunComplianceCascade)->handle($event);

        $this->assertDatabaseHas('helpdesk_compliance_requests', [
            'customer_id' => $customer->id,
            'type' => 'delete_soft',
            'status' => 'pending',
        ], 'helpdesk');
    }

    /**
     * Hallazgo de la auditoría (SEC-05, item 1): el toggle de integración
     * (Settings > Integraciones) NUNCA debe impedir que la cascada legal se
     * encole — solo gatea navegación/UI. Antes, con el toggle apagado, el
     * listener retornaba sin encolar nada y sin dejar rastro.
     */
    public function test_cascade_listener_still_queues_the_job_when_the_integration_toggle_is_disabled(): void
    {
        Queue::fake();

        Setting::set('compliance.integration_enabled', '0', 'integrations');

        $customer = Customer::factory()->create();
        $event = new CustomerGdprDeleted($customer, false, [], ['deleted' => 0, 'anonymized' => 1]);

        (new RunComplianceCascade)->handle($event);

        Queue::assertPushedOn('helpdeskcompliance', ProcessComplianceCascadeJob::class);
        $this->assertDatabaseHas('helpdesk_compliance_requests', [
            'customer_id' => $customer->id,
            'status' => 'pending',
        ], 'helpdesk');

        Setting::set('compliance.integration_enabled', '1', 'integrations');
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

    public function test_job_completes_the_pending_compliance_request(): void
    {
        $customer = Customer::factory()->create();
        $requestId = $this->pendingRequestId($customer->id, false);

        (new ProcessComplianceCascadeJob($requestId, $customer->id, false, [], ['deleted' => 0, 'anonymized' => 1], null))->handle();

        $this->assertDatabaseHas('helpdesk_compliance_requests', [
            'id' => $requestId,
            'customer_id' => $customer->id,
            'type' => 'delete_soft',
            'status' => 'completed',
        ], 'helpdesk');
    }

    public function test_job_hard_mode_is_recorded_as_delete_hard(): void
    {
        $customer = Customer::factory()->create();
        $requestId = $this->pendingRequestId($customer->id, true);

        (new ProcessComplianceCascadeJob($requestId, $customer->id, true, [], ['deleted' => 3, 'anonymized' => 0], null))->handle();

        $this->assertDatabaseHas('helpdesk_compliance_requests', [
            'id' => $requestId,
            'customer_id' => $customer->id,
            'type' => 'delete_hard',
            'status' => 'completed',
        ], 'helpdesk');
    }

    /**
     * Regresión: un fallo de la cascada GDPR debe quedar en el audit trail
     * (gdpr.cascade.failed), no solo en Log::error — antes failed() solo logeaba.
     */
    public function test_job_failure_marks_the_compliance_request_failed_and_writes_audit_log(): void
    {
        $customer = Customer::factory()->create();
        $requestId = $this->pendingRequestId($customer->id, false);

        (new ProcessComplianceCascadeJob($requestId, $customer->id, false, [], ['deleted' => 0, 'anonymized' => 0], null))
            ->failed(new \RuntimeException('boom'));

        $this->assertDatabaseHas('helpdesk_compliance_requests', [
            'id' => $requestId,
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
        $requestId = $this->pendingRequestId($customer->id, true);

        (new ProcessComplianceCascadeJob($requestId, $customer->id, true, [], ['deleted' => 1, 'anonymized' => 0], null))->handle();

        $this->assertDatabaseMissing('helpdesk_tickets', ['id' => $ticket->id], 'helpdesk');
    }

    public function test_job_soft_mode_redacts_and_soft_deletes_tickets(): void
    {
        $customer = Customer::factory()->create();
        $ticket = Ticket::factory()->create(['customer_id' => $customer->id, 'description' => 'Datos personales sensibles', 'subject' => 'Asunto con datos', 'ticket_number' => 'TCK-TEST-'.uniqid()]);
        $requestId = $this->pendingRequestId($customer->id, false);

        (new ProcessComplianceCascadeJob($requestId, $customer->id, false, [], ['deleted' => 0, 'anonymized' => 1], null))->handle();

        $this->assertDatabaseHas('helpdesk_tickets', [
            'id' => $ticket->id,
            'description' => config('helpdeskcompliance.redacted_text'),
            'subject' => config('helpdeskcompliance.redacted_text'),
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
        $requestId = $this->pendingRequestId($customer->id, true);

        (new ProcessComplianceCascadeJob($requestId, $customer->id, true, [$conversation->id], ['deleted' => 1, 'anonymized' => 0], null))->handle();

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
        $requestId = $this->pendingRequestId($customer->id, false);

        (new ProcessComplianceCascadeJob($requestId, $customer->id, false, [$conversation->id], ['deleted' => 0, 'anonymized' => 1], null))->handle();

        $this->assertDatabaseHas('helpdesk_chat_flow_sessions', ['id' => $session->id, 'context' => null], 'helpdesk');
    }
}
