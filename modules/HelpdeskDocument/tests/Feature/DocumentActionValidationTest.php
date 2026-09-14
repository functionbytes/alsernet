<?php

namespace Modules\HelpdeskDocument\Tests\Feature;

use Modules\Document\Entities\Document;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

/**
 * Los Form Requests del puente validan el payload ANTES de delegar en el
 * módulo Document (que valida inline). Cubre las reglas de mayor riesgo:
 * motivo de rechazo, contenido de nota, correo personalizado y la whitelist
 * de campos editables del cliente en update.
 */
class DocumentActionValidationTest extends HelpdeskTestCase
{
    /**
     * @return array{0: Conversation, 1: Document}
     */
    private function makeOwnedExpediente(string $email = 'cliente@example.com'): array
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

    private function urlFor(string $action, Conversation $conversation, Document $document): string
    {
        return route('manager.helpdesk.conversations.documents.'.$action, [$conversation->id, $document->id]);
    }

    public function test_reject_stage_requires_reason_of_at_least_ten_characters(): void
    {
        [$conversation, $document] = $this->makeOwnedExpediente();

        $this->actingAs($this->manager)
            ->postJson($this->urlFor('reject-stage', $conversation, $document), [
                'reason' => 'corto',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_add_note_requires_content(): void
    {
        [$conversation, $document] = $this->makeOwnedExpediente();

        $this->actingAs($this->manager)
            ->postJson($this->urlFor('notes.add', $conversation, $document), [
                'content' => 'ab',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['content']);
    }

    public function test_custom_email_requires_subject_and_message(): void
    {
        [$conversation, $document] = $this->makeOwnedExpediente();

        $this->actingAs($this->manager)
            ->postJson($this->urlFor('send-custom-email', $conversation, $document), [
                'subject' => '',
                'message' => 'hola',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['subject', 'message']);
    }

    public function test_update_rejects_fields_outside_client_whitelist(): void
    {
        [$conversation, $document] = $this->makeOwnedExpediente();
        $originalStatus = $document->status_id;

        $this->actingAs($this->manager)
            ->postJson($this->urlFor('update', $conversation, $document), [
                'data' => ['status_id' => 999, 'customer_firstname' => 'Hack'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['data']);

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'status_id' => $originalStatus,
            'customer_firstname' => 'Test',
        ]);
    }

    public function test_update_accepts_whitelisted_client_fields(): void
    {
        [$conversation, $document] = $this->makeOwnedExpediente();

        $this->actingAs($this->manager)
            ->postJson($this->urlFor('update', $conversation, $document), [
                'data' => [
                    'customer_firstname' => 'María',
                    'customer_email' => 'maria@example.com',
                ],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'customer_firstname' => 'María',
            'customer_email' => 'maria@example.com',
        ]);
    }

    public function test_update_rejects_invalid_email(): void
    {
        [$conversation, $document] = $this->makeOwnedExpediente();

        $this->actingAs($this->manager)
            ->postJson($this->urlFor('update', $conversation, $document), [
                'data' => ['customer_email' => 'no-es-un-email'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['data.customer_email']);
    }
}
