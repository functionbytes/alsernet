<?php

namespace Modules\HelpdeskDocument\Tests\Feature;

use App\Models\User;
use Modules\Document\Entities\Document;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

/**
 * Covers DocumentPanelController::show — the AJAX endpoint that renders the
 * inbox document detail panel. Guards mirror DocumentActionController: the
 * helpdesk permission (route middleware) plus the customer/email ownership
 * check between the conversation and the document.
 */
class DocumentPanelControllerTest extends HelpdeskTestCase
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

    private function panelUrl(Conversation $conversation, Document $document): string
    {
        return route('manager.helpdesk.conversations.documents.panel', [$conversation->id, $document->id]);
    }

    public function test_guest_is_redirected_from_panel(): void
    {
        [$conversation, $document] = $this->makeOwnedExpediente();

        $this->get($this->panelUrl($conversation, $document))
            ->assertRedirect();
    }

    public function test_user_without_permission_cannot_view_panel(): void
    {
        $user = User::factory()->create();
        [$conversation, $document] = $this->makeOwnedExpediente();

        $this->actingAs($user)
            ->get($this->panelUrl($conversation, $document))
            ->assertForbidden();
    }

    public function test_manager_with_ownership_can_view_panel(): void
    {
        [$conversation, $document] = $this->makeOwnedExpediente();

        $this->actingAs($this->manager)
            ->get($this->panelUrl($conversation, $document))
            ->assertOk()
            ->assertViewIs('helpdeskdocument::inbox-slots._document-detail')
            ->assertViewHas('rpDocument', fn (array $rpDocument) => $rpDocument['id'] === $document->id)
            ->assertViewHas('rpConvo', fn (Conversation $rpConvo) => $rpConvo->is($conversation));
    }

    public function test_manager_cannot_view_panel_for_document_of_another_customer(): void
    {
        $customer = Customer::factory()->create(['email' => 'owner@example.com']);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);
        $foreignDocument = Document::create([
            'customer_email' => 'someone-else@example.com',
            'customer_firstname' => 'Other',
            'customer_lastname' => 'Person',
        ]);

        $this->actingAs($this->manager)
            ->get($this->panelUrl($conversation, $foreignDocument))
            ->assertNotFound();
    }

    public function test_manager_can_view_panel_matched_by_phone_when_customer_has_no_email(): void
    {
        $customer = Customer::factory()->create(['email' => '', 'phone' => '+34 611 22 33 44']);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);
        $document = Document::create([
            'customer_email' => 'otro-email@example.com',
            'customer_cellphone' => '611 223 344',
            'customer_firstname' => 'Solo',
            'customer_lastname' => 'Telefono',
        ]);

        $this->actingAs($this->manager)
            ->get($this->panelUrl($conversation, $document))
            ->assertOk();
    }

    public function test_phone_match_does_not_bypass_guard_when_customer_has_email(): void
    {
        $customer = Customer::factory()->create(['email' => 'con-email@example.com', 'phone' => '611223344']);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);
        $document = Document::create([
            'customer_email' => 'otro-email@example.com',
            'customer_cellphone' => '611223344',
            'customer_firstname' => 'Other',
            'customer_lastname' => 'Person',
        ]);

        $this->actingAs($this->manager)
            ->get($this->panelUrl($conversation, $document))
            ->assertNotFound();
    }

    public function test_manager_can_view_panel_for_document_manually_linked_via_metadata(): void
    {
        $customer = Customer::factory()->create(['email' => 'owner@example.com']);
        $linkedDocument = Document::create([
            'customer_email' => 'someone-else@example.com',
            'customer_firstname' => 'Other',
            'customer_lastname' => 'Person',
        ]);
        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'metadata' => ['document_id' => $linkedDocument->id],
        ]);

        $this->actingAs($this->manager)
            ->get($this->panelUrl($conversation, $linkedDocument))
            ->assertOk()
            ->assertViewIs('helpdeskdocument::inbox-slots._document-detail')
            ->assertViewHas('rpDocument', fn (array $rpDocument) => $rpDocument['id'] === $linkedDocument->id);
    }
}
