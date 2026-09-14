<?php

namespace Modules\HelpdeskDocument\Tests\Feature;

use App\Models\User;
use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentStatus;
use Modules\Document\Entities\DocumentType;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Tests\HelpdeskTestCase;
use Modules\HelpdeskDocument\Services\ConversationDocumentLinker;

/**
 * Creación de expedientes desde el inbox (botón "+ Nuevo expediente"):
 * opciones para el modal + store con vínculo automático a la conversación.
 */
class DocumentCreateControllerTest extends HelpdeskTestCase
{
    /**
     * @return array{0: Conversation, 1: Customer}
     */
    private function makeConversation(array $customerAttrs = []): array
    {
        $customer = Customer::factory()->create(array_merge(['email' => 'nuevo@example.com'], $customerAttrs));
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);

        return [$conversation, $customer];
    }

    public function test_guest_is_redirected_from_create_options(): void
    {
        [$conversation] = $this->makeConversation();

        $this->get(route('manager.helpdesk.conversations.documents.create-options', $conversation->id))
            ->assertRedirect();
    }

    public function test_user_without_permission_cannot_create_document(): void
    {
        $user = User::factory()->create();
        [$conversation] = $this->makeConversation();

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.conversations.documents.store', $conversation->id))
            ->assertForbidden();
    }

    public function test_options_returns_active_document_types_and_customer(): void
    {
        [$conversation, $customer] = $this->makeConversation();
        DocumentType::firstOrCreate(['slug' => 'dni'], ['label' => 'DNI', 'is_active' => true]);

        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.conversations.documents.create-options', $conversation->id))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('customer.name', $customer->name)
            ->assertJsonStructure(['types' => [['id', 'label']]]);
    }

    public function test_manager_can_create_document_linked_to_conversation(): void
    {
        [$conversation, $customer] = $this->makeConversation();
        $type = DocumentType::firstOrCreate(['slug' => 'dni'], ['label' => 'DNI', 'is_active' => true]);

        $response = $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.documents.store', $conversation->id), [
                'type_id' => $type->id,
                'order_reference' => 'REF-777',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $documentId = $response->json('document.id');

        $this->assertDatabaseHas('documents', [
            'id' => $documentId,
            'customer_email' => $customer->email,
            'order_reference' => 'REF-777',
            'type_id' => $type->id,
        ]);

        // Vinculado a la conversación de inmediato.
        $this->assertSame($documentId, $conversation->fresh()->metadata['document_id']);
    }

    public function test_store_rejects_customer_without_email_or_phone(): void
    {
        $customer = Customer::factory()->create(['email' => '', 'phone' => null, 'whatsapp_phone' => null]);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.documents.store', $conversation->id))
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_store_rejects_unknown_document_type(): void
    {
        [$conversation] = $this->makeConversation();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.documents.store', $conversation->id), [
                'type_id' => 999999,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type_id']);
    }

    /**
     * Regresión: asignar (vincular) un segundo expediente manualmente ya no
     * debe desalojar al primero — antes ConversationDocumentLinker::link()
     * sobrescribía metadata.document_id (escalar), así que el primer
     * expediente perdía su único vínculo y desaparecía del listado.
     */
    public function test_linking_second_document_does_not_evict_first_from_conversation(): void
    {
        [$conversation] = $this->makeConversation();
        $status = DocumentStatus::firstOrCreate(
            ['key' => 'received'],
            ['label' => 'Recibido', 'color' => '#000000', 'is_active' => true, 'order' => 1]
        );

        // Emails distintos al de la conversación: solo aparecen en el panel
        // por el vínculo manual, que es exactamente el escenario del bug.
        $first = Document::create([
            'customer_email' => 'otro-cliente-1@example.com',
            'customer_firstname' => 'Uno',
            'customer_lastname' => 'Cliente',
            'status_id' => $status->id,
        ]);
        $second = Document::create([
            'customer_email' => 'otro-cliente-2@example.com',
            'customer_firstname' => 'Dos',
            'customer_lastname' => 'Cliente',
            'status_id' => $status->id,
        ]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.documents.link', [$conversation->id, $first->id]))
            ->assertOk();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.documents.link', [$conversation->id, $second->id]))
            ->assertOk();

        $metadata = $conversation->fresh()->metadata;
        $this->assertContains($first->id, $metadata['document_ids']);
        $this->assertContains($second->id, $metadata['document_ids']);

        $linked = app(ConversationDocumentLinker::class)->documentsForConversation($conversation->fresh());
        $this->assertTrue($linked->contains('id', $first->id), 'El primer expediente vinculado no debe desaparecer del listado.');
        $this->assertTrue($linked->contains('id', $second->id));

        // El primer expediente sigue siendo accesible (sin 404) tras vincular el segundo.
        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.conversations.documents.panel', [$conversation->id, $first->id]))
            ->assertOk();
    }
}
