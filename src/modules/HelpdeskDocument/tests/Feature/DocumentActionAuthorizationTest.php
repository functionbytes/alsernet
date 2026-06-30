<?php

namespace Modules\HelpdeskDocument\Tests\Feature;

use App\Models\User;
use Modules\Document\Entities\Document;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

/**
 * Guards the helpdesk-scoped proxy endpoints that replace the weakly-protected
 * `api.documents.*` mutating routes. Every action must require the helpdesk
 * permission AND the conversation↔document ownership (customer email match).
 */
class DocumentActionAuthorizationTest extends HelpdeskTestCase
{
    /**
     * The full set of mutating endpoints exposed by DocumentActionController.
     *
     * @return array<int, array{0: string, 1: string, 2: bool}>
     *                                                          [route name suffix, HTTP verb, needs trailing id binding]
     */
    private function mutatingActions(): array
    {
        return [
            ['upload', 'post', false],
            ['assign', 'post', false],
            ['approve-stage', 'post', false],
            ['reject-stage', 'post', false],
            ['send-notification', 'post', false],
            ['send-reminder', 'post', false],
            ['send-upload-confirmation', 'post', false],
            ['send-approval', 'post', false],
            ['send-missing', 'post', false],
            ['send-rejection', 'post', false],
            ['send-custom-email', 'post', false],
            ['notes.add', 'post', false],
            ['notes.delete', 'delete', true],
            ['upload-attachment', 'post', false],
            ['delete-attachment', 'delete', true],
            ['update', 'post', false],
        ];
    }

    /**
     * Create a conversation and a document that belong to the same customer.
     *
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

    private function urlFor(string $action, Conversation $conversation, Document $document, bool $needsId): string
    {
        $params = [$conversation->id, $document->id];

        if ($needsId) {
            $params[] = 1; // noteId / mediaId placeholder
        }

        return route('manager.helpdesk.conversations.documents.'.$action, $params);
    }

    public function test_guest_is_redirected_from_mutating_action(): void
    {
        [$conversation, $document] = $this->makeOwnedExpediente();

        $this->post($this->urlFor('assign', $conversation, $document, false), [
            'assigned_user_id' => null,
        ])->assertRedirect();
    }

    public function test_user_without_permission_cannot_perform_any_mutating_action(): void
    {
        $user = User::factory()->create();
        [$conversation, $document] = $this->makeOwnedExpediente();

        foreach ($this->mutatingActions() as [$action, $verb, $needsId]) {
            $url = $this->urlFor($action, $conversation, $document, $needsId);

            $this->actingAs($user)
                ->{$verb}($url)
                ->assertForbidden();
        }
    }

    public function test_manager_with_ownership_can_assign_validator(): void
    {
        [$conversation, $document] = $this->makeOwnedExpediente();

        $this->actingAs($this->manager)
            ->postJson($this->urlFor('assign', $conversation, $document, false), [
                'assigned_user_id' => $this->manager->id,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'assigned_user_id' => $this->manager->id,
        ]);
    }

    public function test_manager_with_ownership_can_update_document_fields(): void
    {
        [$conversation, $document] = $this->makeOwnedExpediente();

        $this->actingAs($this->manager)
            ->postJson($this->urlFor('update', $conversation, $document, false), [
                'data' => ['customer_company' => 'ACME'],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'customer_company' => 'ACME',
        ]);
    }

    public function test_manager_cannot_act_on_document_of_another_customer(): void
    {
        $customer = Customer::factory()->create(['email' => 'owner@example.com']);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);
        $foreignDocument = Document::create([
            'customer_email' => 'someone-else@example.com',
            'customer_firstname' => 'Other',
            'customer_lastname' => 'Person',
        ]);

        $this->actingAs($this->manager)
            ->postJson($this->urlFor('assign', $conversation, $foreignDocument, false), [
                'assigned_user_id' => $this->manager->id,
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('documents', [
            'id' => $foreignDocument->id,
            'assigned_user_id' => $this->manager->id,
        ]);
    }
}
