<?php

namespace Modules\Helpdesk\Tests\Feature\Compliance;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Document\Entities\Document;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\Compliance\GdprExportService;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use Modules\HelpdeskTickets\Models\Ticket;
use Tests\TestCase;

/**
 * Hallazgo de auditoría: el export GDPR (derecho de acceso) solo cubría datos
 * core (conversaciones/CSAT/NPS/drip/audit/tags) y omitía Tickets, sesiones de
 * ChatFlow y expedientes de Document. Cubre el punto de extensión
 * GdprExportContributor (servicios etiquetados por módulo).
 */
class GdprExportContributorsTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = [null, 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function export(Customer $customer): array
    {
        return app(GdprExportService::class)->exportCustomer($customer);
    }

    public function test_export_always_contains_the_module_sections(): void
    {
        $customer = Customer::factory()->create();

        $data = $this->export($customer);

        // Aunque el cliente no tenga datos en los módulos, las secciones deben
        // existir (vacías): un export sin la clave no permite distinguir "sin
        // datos" de "módulo no exportado".
        $this->assertArrayHasKey('tickets', $data);
        $this->assertArrayHasKey('chatflow_sessions', $data);
        $this->assertArrayHasKey('documents', $data);
        $this->assertSame([], $data['tickets']);
        $this->assertSame([], $data['chatflow_sessions']);
        $this->assertSame([], $data['documents']);
    }

    public function test_export_includes_customer_tickets_with_non_internal_messages(): void
    {
        $customer = Customer::factory()->create();
        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'subject' => 'Pedido no recibido',
            'ticket_number' => 'TCK-TEST-'.uniqid(),
        ]);

        $ticket->items()->create([
            'author_id' => $customer->id,
            'type' => 'message',
            'body' => 'Mensaje del cliente',
            'is_internal' => false,
        ]);
        $ticket->items()->create([
            'user_id' => null,
            'type' => 'message',
            'body' => 'Nota interna del agente',
            'is_internal' => true,
        ]);

        $data = $this->export($customer);

        $this->assertCount(1, $data['tickets']);
        $exported = $data['tickets'][0];
        $this->assertSame('Pedido no recibido', $exported['subject']);

        $bodies = array_column($exported['messages'], 'body');
        $this->assertContains('Mensaje del cliente', $bodies);
        $this->assertNotContains('Nota interna del agente', $bodies, 'Las notas internas de agente no forman parte de los datos del interesado.');
    }

    public function test_export_includes_chatflow_sessions_of_the_customer_conversations(): void
    {
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);
        $flow = ChatFlow::factory()->create();

        ChatFlowSession::create([
            'chat_flow_id' => $flow->id,
            'conversation_id' => $conversation->id,
            'status' => 'completed',
            'context' => ['telefono' => '600111222'],
            'trigger_type' => 'manual',
            'started_at' => now(),
        ]);

        // Sesión de OTRO cliente: no debe filtrarse al export.
        $otherConversation = Conversation::factory()->create();
        ChatFlowSession::create([
            'chat_flow_id' => $flow->id,
            'conversation_id' => $otherConversation->id,
            'status' => 'completed',
            'context' => ['telefono' => 'ajeno'],
            'trigger_type' => 'manual',
            'started_at' => now(),
        ]);

        $data = $this->export($customer);

        $this->assertCount(1, $data['chatflow_sessions']);
        $this->assertSame(['telefono' => '600111222'], $data['chatflow_sessions'][0]['context']);
    }

    public function test_export_includes_documents_matched_by_email_with_file_inventory(): void
    {
        $customer = Customer::factory()->create(['email' => 'export-kyc@example.test']);

        $document = Document::create([
            'customer_email' => 'export-kyc@example.test',
            'customer_firstname' => 'Cliente',
            'customer_lastname' => 'Export',
            'customer_dni' => '11111111H',
        ]);
        $document->addMedia(UploadedFile::fake()->create('dni_frontal.pdf', 60, 'application/pdf'))
            ->withCustomProperties(['document_type' => 'dni_frontal'])
            ->toMediaCollection('documents');

        // Expediente de otro cliente: fuera del export.
        Document::create(['customer_email' => 'otro@example.test']);

        $data = $this->export($customer);

        $this->assertCount(1, $data['documents']);
        $exported = $data['documents'][0];
        $this->assertSame('11111111H', $exported['customer_dni']);
        $this->assertCount(1, $exported['files']);
        $this->assertSame('dni_frontal', $exported['files'][0]['document_type']);
        $this->assertSame('dni_frontal.pdf', $exported['files'][0]['file_name']);
    }

    public function test_export_includes_documents_matched_by_phone_when_customer_has_no_email(): void
    {
        $customer = Customer::factory()->create(['email' => null, 'whatsapp_phone' => '34600111222']);

        $document = Document::create([
            'customer_email' => null,
            'customer_cellphone' => '600 11 12 22',
            'customer_firstname' => 'Solo',
            'customer_lastname' => 'Whatsapp',
        ]);

        $data = $this->export($customer);

        $this->assertCount(1, $data['documents']);
        $this->assertSame($document->id, $data['documents'][0]['id']);
    }
}
