<?php

namespace Modules\HelpdeskDocument\Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Modules\Document\Entities\Document;
use Modules\Document\Jobs\MailTemplateJob;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

/**
 * Happy paths de las acciones mutadoras del puente que hasta ahora solo
 * tenían cobertura de autorización: notas, adjuntos internos, subida de
 * documentación y correos (send-*). Los correos se encolan como
 * MailTemplateJob en el módulo Document — se asserta el dispatch, no el
 * envío real.
 */
class DocumentActionHappyPathsTest extends HelpdeskTestCase
{
    /**
     * @return array{0: Conversation, 1: Document}
     */
    private function makeOwnedExpediente(string $email = 'acciones@example.com'): array
    {
        $customer = Customer::factory()->create(['email' => $email]);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);
        $document = Document::create([
            'customer_email' => $email,
            'customer_firstname' => 'Test',
            'customer_lastname' => 'Cliente',
        ]);

        return [$conversation, $document];
    }

    private function urlFor(string $action, Conversation $conversation, Document $document, ?int $extraId = null): string
    {
        $params = [$conversation->id, $document->id];

        if ($extraId !== null) {
            $params[] = $extraId;
        }

        return route('manager.helpdesk.conversations.documents.'.$action, $params);
    }

    // ─── Notas internas ───────────────────────────────────────────────────────

    public function test_manager_can_add_internal_note(): void
    {
        [$conversation, $document] = $this->makeOwnedExpediente();

        $this->actingAs($this->manager)
            ->postJson($this->urlFor('notes.add', $conversation, $document), [
                'content' => 'Cliente avisado del reenvío del DNI.',
                'is_internal' => 1,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('document_notes', [
            'document_id' => $document->id,
            'content' => 'Cliente avisado del reenvío del DNI.',
        ]);
    }

    /**
     * Regresión: al delegar en el módulo Document, su guard interno
     * canDocument('add-notes') exigía pertenecer a un grupo validador y
     * dejaba en 403 a los agentes del inbox con helpdesk.documents.manage.
     * El puente crea la nota directamente, así que un agente normal (sin
     * grupo validador de Document) debe poder añadir notas.
     */
    public function test_agent_without_document_validator_group_can_add_note(): void
    {
        $agent = User::factory()->create();
        $agent->givePermissionTo(['helpdesk.documents.manage', 'helpdesk.customers.view', 'helpdesk.customers.manage']);
        [$conversation, $document] = $this->makeOwnedExpediente();

        $this->actingAs($agent)
            ->postJson($this->urlFor('notes.add', $conversation, $document), [
                'content' => 'Nota de agente sin grupo validador.',
                'is_internal' => 1,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('document_notes', [
            'document_id' => $document->id,
            'content' => 'Nota de agente sin grupo validador.',
            'created_by' => $agent->id,
        ]);
    }

    public function test_manager_can_delete_note(): void
    {
        [$conversation, $document] = $this->makeOwnedExpediente();
        $note = $document->notes()->create([
            'content' => 'Nota temporal',
            'is_internal' => true,
            'created_by' => $this->manager->id,
        ]);

        $this->actingAs($this->manager)
            ->deleteJson($this->urlFor('notes.delete', $conversation, $document, $note->id))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('document_notes', ['id' => $note->id]);
    }

    // ─── Adjuntos internos ────────────────────────────────────────────────────

    public function test_manager_can_upload_internal_attachment(): void
    {
        [$conversation, $document] = $this->makeOwnedExpediente();

        $this->actingAs($this->manager)
            ->post($this->urlFor('upload-attachment', $conversation, $document), [
                'file' => UploadedFile::fake()->create('verificacion.pdf', 120, 'application/pdf'),
                'notes' => 'Verificación interna',
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertCount(1, $document->fresh()->getMedia('additional_attachments'));
    }

    public function test_manager_can_delete_internal_attachment(): void
    {
        [$conversation, $document] = $this->makeOwnedExpediente();

        $media = $document
            ->addMedia(UploadedFile::fake()->create('borrar.pdf', 80, 'application/pdf'))
            ->toMediaCollection('additional_attachments');

        $this->actingAs($this->manager)
            ->deleteJson($this->urlFor('delete-attachment', $conversation, $document, $media->id))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertCount(0, $document->fresh()->getMedia('additional_attachments'));
    }

    // ─── Subida de documentación del expediente ───────────────────────────────

    public function test_manager_can_upload_missing_document_file(): void
    {
        [$conversation, $document] = $this->makeOwnedExpediente();

        $this->actingAs($this->manager)
            ->post($this->urlFor('upload', $conversation, $document), [
                'documents' => ['dni_front' => UploadedFile::fake()->image('dni-frontal.jpg')],
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $media = $document->fresh()->getMedia('documents');
        $this->assertCount(1, $media);
        $this->assertSame('dni_front', $media->first()->getCustomProperty('document_type'));
    }

    // ─── Correos (send-*): se encolan como MailTemplateJob ──────────────────

    public function test_send_reminder_queues_mail_job(): void
    {
        Queue::fake();
        [$conversation, $document] = $this->makeOwnedExpediente();

        $this->actingAs($this->manager)
            ->postJson($this->urlFor('send-reminder', $conversation, $document))
            ->assertOk()
            ->assertJson(['success' => true]);

        Queue::assertPushed(MailTemplateJob::class);
    }

    public function test_send_custom_email_queues_mail_job(): void
    {
        Queue::fake();
        [$conversation, $document] = $this->makeOwnedExpediente();

        $this->actingAs($this->manager)
            ->postJson($this->urlFor('send-custom-email', $conversation, $document), [
                'subject' => 'Información sobre su expediente',
                'message' => 'Le escribimos en relación a su documentación pendiente.',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        Queue::assertPushed(MailTemplateJob::class);
    }

    public function test_send_missing_documents_queues_mail_job(): void
    {
        Queue::fake();
        [$conversation, $document] = $this->makeOwnedExpediente();

        $this->actingAs($this->manager)
            ->postJson($this->urlFor('send-missing', $conversation, $document), [
                'missing_docs' => ['dni_front'],
                'notes' => 'Falta la cara frontal del DNI.',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        Queue::assertPushed(MailTemplateJob::class);
    }

    public function test_send_email_fails_gracefully_without_customer_email(): void
    {
        Queue::fake();
        $customer = Customer::factory()->create(['email' => '', 'phone' => '611223344']);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);
        $document = Document::create([
            'customer_email' => '',
            'customer_cellphone' => '611223344',
            'customer_firstname' => 'Sin',
            'customer_lastname' => 'Email',
        ]);

        $this->actingAs($this->manager)
            ->postJson($this->urlFor('send-reminder', $conversation, $document))
            ->assertUnprocessable()
            ->assertJsonPath('error_type', 'missing_email');

        Queue::assertNothingPushed();
    }
}
