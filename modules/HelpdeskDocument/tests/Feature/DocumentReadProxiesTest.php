<?php

namespace Modules\HelpdeskDocument\Tests\Feature;

use App\Models\User;
use Modules\Document\Entities\Document;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

/**
 * Proxies de lectura del expediente (historial de acciones y descarga ZIP).
 * Antes el tab consumía directamente `api.documents.action-history` y
 * `api.documents.download-zip`, protegidas por rol super-admin|supervisor:
 * un agente normal del inbox recibía 403. Estos endpoints los sustituyen con
 * el permiso helpdesk + ownership conversación↔documento.
 */
class DocumentReadProxiesTest extends HelpdeskTestCase
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

    /**
     * @return array{0: Conversation, 1: Document}
     */
    private function makeForeignExpediente(): array
    {
        $customer = Customer::factory()->create(['email' => 'owner@example.com']);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);
        $foreignDocument = Document::create([
            'customer_email' => 'someone-else@example.com',
            'customer_firstname' => 'Other',
            'customer_lastname' => 'Person',
        ]);

        return [$conversation, $foreignDocument];
    }

    // ─── action-history ───────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_action_history(): void
    {
        [$conversation, $document] = $this->makeOwnedExpediente();

        $this->get(route('manager.helpdesk.conversations.documents.action-history', [$conversation->id, $document->id]))
            ->assertRedirect();
    }

    public function test_user_without_permission_cannot_view_action_history(): void
    {
        $user = User::factory()->create();
        [$conversation, $document] = $this->makeOwnedExpediente();

        $this->actingAs($user)
            ->getJson(route('manager.helpdesk.conversations.documents.action-history', [$conversation->id, $document->id]))
            ->assertForbidden();
    }

    public function test_manager_with_ownership_can_view_action_history(): void
    {
        [$conversation, $document] = $this->makeOwnedExpediente();

        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.conversations.documents.action-history', [$conversation->id, $document->id]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'history']);
    }

    public function test_manager_cannot_view_history_of_foreign_document(): void
    {
        [$conversation, $foreignDocument] = $this->makeForeignExpediente();

        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.conversations.documents.action-history', [$conversation->id, $foreignDocument->id]))
            ->assertNotFound();
    }

    // ─── download-zip ─────────────────────────────────────────────────────────

    public function test_user_without_permission_cannot_download_zip(): void
    {
        $user = User::factory()->create();
        [$conversation, $document] = $this->makeOwnedExpediente();

        $this->actingAs($user)
            ->getJson(route('manager.helpdesk.conversations.documents.download-zip', [$conversation->id, $document->id]))
            ->assertForbidden();
    }

    public function test_manager_download_zip_without_files_returns_not_found_message(): void
    {
        [$conversation, $document] = $this->makeOwnedExpediente();

        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.conversations.documents.download-zip', [$conversation->id, $document->id]))
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }

    public function test_manager_cannot_download_zip_of_foreign_document(): void
    {
        [$conversation, $foreignDocument] = $this->makeForeignExpediente();

        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.conversations.documents.download-zip', [$conversation->id, $foreignDocument->id]))
            ->assertNotFound();
    }
}
