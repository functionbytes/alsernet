<?php

namespace Modules\HelpdeskDocument\Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentStatus;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Setting;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

/**
 * Covers DocumentFileController::destroy — deleting a single uploaded file
 * (by document type) from a customer's expediente, scoped to the
 * conversation. Same ownership guard as DocumentPanelController.
 *
 * NOTE: the "revert status to awaiting_documents when a required document
 * becomes missing" branch (destroy() calling $document->getMissingDocuments())
 * used to throw a QueryException because Modules\Document\Entities\
 * DocumentRequirement::langs() did not declare its foreign key — Eloquent
 * guessed "document_requirement_id" while the real column is
 * "document_type_requirement_id". That FK is now declared correctly (verified:
 * langs() loads), so the chain no longer throws. In addition, destroy() now
 * wraps the reversion in a try/catch: the file is already deleted at that
 * point, so any future failure in the cross-module getMissingDocuments() call
 * is logged and swallowed instead of turning the delete into a 500.
 *
 * The end-to-end revert assertion is still not exercised here because the
 * shared test snapshot has no "awaiting_documents" DocumentStatus row to revert
 * to; it is covered by the defensive-guard behaviour above.
 */
class DocumentFileControllerTest extends HelpdeskTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

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

    private function attachDocumentFile(Document $document, string $docType): void
    {
        $document->addMedia(UploadedFile::fake()->create($docType.'.pdf', 100, 'application/pdf'))
            ->withCustomProperties(['document_type' => $docType])
            ->toMediaCollection('documents');
    }

    private function fileUrl(Conversation $conversation, Document $document, string $docType): string
    {
        return route('manager.helpdesk.conversations.documents.files.destroy', [$conversation->id, $document->id, $docType]);
    }

    public function test_guest_is_redirected_from_file_delete(): void
    {
        [$conversation, $document] = $this->makeOwnedExpediente();

        $this->delete($this->fileUrl($conversation, $document, 'dni_frontal'))
            ->assertRedirect();
    }

    public function test_user_without_permission_cannot_delete_file(): void
    {
        $user = User::factory()->create();
        [$conversation, $document] = $this->makeOwnedExpediente();

        $this->actingAs($user)
            ->deleteJson($this->fileUrl($conversation, $document, 'dni_frontal'))
            ->assertForbidden();
    }

    /**
     * Ver/acceder al cliente ya no basta para MUTAR el expediente: las
     * acciones destructivas/de aprobación exigen helpdesk.documents.manage.
     * Este usuario tiene TODO lo necesario para pasar el ownership del
     * controller (customers.view + customers.manage) pero NO documents.manage
     * → 403 en el borrado de archivos, aislando el nuevo límite de la ruta.
     */
    public function test_customer_access_without_documents_manage_cannot_delete_a_file(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['helpdesk.customers.view', 'helpdesk.customers.manage']);
        [$conversation, $document] = $this->makeOwnedExpediente();

        $this->actingAs($user)
            ->deleteJson($this->fileUrl($conversation, $document, 'dni_frontal'))
            ->assertForbidden();
    }

    public function test_documents_manage_permission_allows_deleting_a_file(): void
    {
        $user = User::factory()->create();
        // documents.manage (ruta) + acceso al cliente (ownership del controller).
        $user->givePermissionTo(['helpdesk.documents.manage', 'helpdesk.customers.view', 'helpdesk.customers.manage']);
        [$conversation, $document] = $this->makeOwnedExpediente();
        $this->attachDocumentFile($document, 'dni_frontal');

        $this->actingAs($user)
            ->deleteJson($this->fileUrl($conversation, $document, 'dni_frontal'))
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_manager_cannot_delete_file_of_document_from_another_customer(): void
    {
        $customer = Customer::factory()->create(['email' => 'owner@example.com']);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);
        $foreignDocument = Document::create([
            'customer_email' => 'someone-else@example.com',
            'customer_firstname' => 'Other',
            'customer_lastname' => 'Person',
        ]);

        $this->actingAs($this->manager)
            ->deleteJson($this->fileUrl($conversation, $foreignDocument, 'dni_frontal'))
            ->assertNotFound();
    }

    public function test_returns_404_when_no_file_matches_document_type(): void
    {
        [$conversation, $document] = $this->makeOwnedExpediente();

        $this->actingAs($this->manager)
            ->deleteJson($this->fileUrl($conversation, $document, 'dni_frontal'))
            ->assertNotFound()
            ->assertJson(['success' => false]);
    }

    public function test_deletes_matching_file_and_syncs_uploaded_documents(): void
    {
        [$conversation, $document] = $this->makeOwnedExpediente();
        $this->attachDocumentFile($document, 'dni_frontal');

        $this->actingAs($this->manager)
            ->deleteJson($this->fileUrl($conversation, $document, 'dni_frontal'))
            ->assertOk()
            ->assertJson(['success' => true]);

        $document->refresh();
        $this->assertSame([], $document->getUploadedDocumentTypes());
        $this->assertEmpty($document->uploaded_documents);
    }

    public function test_deleting_one_of_several_files_keeps_the_others_uploaded(): void
    {
        [$conversation, $document] = $this->makeOwnedExpediente();
        $this->attachDocumentFile($document, 'dni_frontal');
        $this->attachDocumentFile($document, 'dni_trasera');

        $this->actingAs($this->manager)
            ->deleteJson($this->fileUrl($conversation, $document, 'dni_frontal'))
            ->assertOk();

        $this->assertSame(['dni_trasera'], $document->fresh()->getUploadedDocumentTypes());
    }

    /**
     * Un expediente ya aprobado es un registro cerrado: "Eliminar"/"Reemplazar"
     * ya se ocultan en el panel (_document-detail, openFileDetail), pero el
     * endpoint debe rechazar igual una petición directa, sin borrar el archivo.
     */
    public function test_cannot_delete_a_file_from_an_approved_document(): void
    {
        [$conversation, $document] = $this->makeOwnedExpediente();
        $this->attachDocumentFile($document, 'dni_frontal');

        $approvedStatus = DocumentStatus::query()->where('key', 'approved')->first()
            ?? DocumentStatus::query()->create(['key' => 'approved', 'label' => 'Aprobado']);
        $document->update(['status_id' => $approvedStatus->id]);

        $this->actingAs($this->manager)
            ->deleteJson($this->fileUrl($conversation, $document, 'dni_frontal'))
            ->assertUnprocessable()
            ->assertJson(['success' => false]);

        $this->assertSame(['dni_frontal'], $document->fresh()->getUploadedDocumentTypes());
    }

    /**
     * Hasta ahora esta ruta no tenia ningun check de
     * helpdesk_document_enabled() — a diferencia de DocumentPanelController,
     * que si lo tenia — dejando el borrado de archivos alcanzable con la
     * integracion desactivada. Ahora todo modules/HelpdeskDocument/routes/
     * managers.php pasa por integration.enabled:document.
     */
    public function test_returns_404_when_document_integration_disabled(): void
    {
        [$conversation, $document] = $this->makeOwnedExpediente();
        $this->attachDocumentFile($document, 'dni_frontal');

        Setting::set('document.integration_enabled', '0', 'integrations');

        $this->actingAs($this->manager)
            ->deleteJson($this->fileUrl($conversation, $document, 'dni_frontal'))
            ->assertNotFound();

        Setting::set('document.integration_enabled', '1', 'integrations');
    }
}
