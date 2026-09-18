<?php

namespace Modules\HelpdeskDocument\Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentRequirement;
use Modules\Document\Entities\DocumentType;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Tests\HelpdeskTestCase;
use Modules\HelpdeskDocument\Services\DocumentPanelPresenter;

/**
 * Covers ChatGalleryDocumentController::importFromChat/importFromDevice —
 * importing chat attachments or agent device uploads into a customer's
 * expediente (when linked via conversation.metadata.document_id) or, absent
 * a link, just recording the import on the conversation metadata.
 */
class ChatGalleryDocumentControllerTest extends HelpdeskTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    /**
     * @return array{0: Conversation, 1: Customer}
     */
    private function makeConversation(string $email = 'cliente@example.com'): array
    {
        $customer = Customer::factory()->create(['email' => $email]);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);

        return [$conversation, $customer];
    }

    private function linkDocument(Conversation $conversation, Document $document): void
    {
        $conversation->forceFill(['metadata' => ['document_id' => $document->id]])->save();
    }

    private function attachmentItem(Conversation $conversation, array $urls): ConversationItem
    {
        return ConversationItem::factory()->create([
            'conversation_id' => $conversation->id,
            'attachment_urls' => $urls,
        ]);
    }

    private function attachRequiredImage(Document $document, string $type, string $name): void
    {
        $document->addMedia(UploadedFile::fake()->image($name))
            ->usingFileName($name)
            ->withCustomProperties([
                'document_type' => $type,
                'upload_type' => $type,
                'source' => 'test_fixture',
            ])
            ->toMediaCollection('documents');
    }

    private function makeDocumentTypeWithRequirements(array $requirements): DocumentType
    {
        $type = DocumentType::create([
            'slug' => 'codex-functional-'.uniqid(),
            'label' => 'Codex funcional',
            'description' => 'Tipo documental para pruebas funcionales',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        foreach (array_values($requirements) as $i => $key) {
            DocumentRequirement::create([
                'document_type_id' => $type->id,
                'key' => $key,
                'is_required' => true,
                'accepts_multiple' => false,
                'sort_order' => $i + 1,
            ]);
        }

        return $type->load('requirements');
    }

    // ── importFromChat ──────────────────────────────────────────────────

    public function test_guest_is_redirected_from_import_from_chat(): void
    {
        [$conversation] = $this->makeConversation();

        $this->post(route('manager.helpdesk.conversations.documents.import-from-chat', $conversation->id), [
            'file_ids' => ['/storage/x.jpg'],
        ])->assertRedirect();
    }

    public function test_user_without_permission_cannot_import_from_chat(): void
    {
        $user = User::factory()->create();
        [$conversation] = $this->makeConversation();

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.conversations.documents.import-from-chat', $conversation->id), [
                'file_ids' => ['/storage/x.jpg'],
            ])
            ->assertForbidden();
    }

    public function test_import_from_chat_validation_rejects_empty_file_ids(): void
    {
        [$conversation] = $this->makeConversation();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.documents.import-from-chat', $conversation->id), [
                'file_ids' => [],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file_ids']);
    }

    public function test_import_from_chat_rejects_files_not_belonging_to_conversation(): void
    {
        [$conversation] = $this->makeConversation();
        $this->attachmentItem($conversation, [['url' => '/storage/real.jpg', 'name' => 'real.jpg']]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.documents.import-from-chat', $conversation->id), [
                'file_ids' => ['/storage/not-shared-here.jpg'],
            ])
            ->assertUnprocessable()
            ->assertJson([
                'success' => false,
                'message' => 'Los archivos seleccionados no pertenecen a esta conversación.',
            ]);
    }

    public function test_import_from_chat_rejects_non_image_shared_file(): void
    {
        [$conversation] = $this->makeConversation();
        $this->attachmentItem($conversation, [['url' => '/storage/contract.pdf', 'name' => 'contract.pdf', 'type' => 'document']]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.documents.import-from-chat', $conversation->id), [
                'file_ids' => ['/storage/contract.pdf'],
            ])
            ->assertUnprocessable()
            ->assertJson([
                'success' => false,
                'message' => 'Solo se pueden importar imágenes al expediente.',
            ]);
    }

    public function test_import_from_chat_without_linked_document_auto_creates_expediente(): void
    {
        Storage::disk('public')->put('chat-imports/auto/real.jpg', 'fake-content');
        $url = Storage::disk('public')->url('chat-imports/auto/real.jpg');

        [$conversation, $customer] = $this->makeConversation();
        $this->attachmentItem($conversation, [['url' => $url, 'name' => 'real.jpg']]);

        $response = $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.documents.import-from-chat', $conversation->id), [
                'file_ids' => [$url],
            ])
            ->assertOk();

        // Antes los archivos quedaban solo en metadata "pendientes de creación
        // real": ahora se crea un expediente para el cliente y se vincula.
        $response->assertJson([
            'success' => true,
            'linkedDocument' => true,
            'importedCount' => 1,
        ]);

        $documentId = $response->json('documentId');
        $this->assertNotNull($documentId);
        $this->assertDatabaseHas('documents', [
            'id' => $documentId,
            'customer_email' => $customer->email,
        ]);
        $this->assertSame($documentId, $conversation->fresh()->metadata['document_id']);
    }

    public function test_import_without_match_key_still_records_metadata_only(): void
    {
        // Cliente sin email ni teléfono normalizable: no se puede auto-crear
        // (el expediente quedaría huérfano) → comportamiento anterior.
        $customer = Customer::factory()->create(['email' => '', 'phone' => null, 'whatsapp_phone' => null]);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);
        $this->attachmentItem($conversation, [['url' => '/storage/real.jpg', 'name' => 'real.jpg']]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.documents.import-from-chat', $conversation->id), [
                'file_ids' => ['/storage/real.jpg'],
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'linkedDocument' => false,
                'documentId' => null,
            ]);

        $imports = $conversation->fresh()->metadata['chat_document_imports'] ?? [];
        $this->assertCount(1, $imports);
    }

    public function test_import_from_chat_attaches_local_file_to_linked_document(): void
    {
        Storage::disk('public')->put('chat-imports/attach-test/photo.jpg', 'fake-content');
        $url = Storage::disk('public')->url('chat-imports/attach-test/photo.jpg');

        [$conversation] = $this->makeConversation();
        $document = Document::create([
            'customer_email' => 'cliente@example.com',
            'customer_firstname' => 'Test',
            'customer_lastname' => 'Cliente',
        ]);
        $this->linkDocument($conversation, $document);
        $this->attachmentItem($conversation, [['url' => $url, 'name' => 'photo.jpg']]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.documents.import-from-chat', $conversation->id), [
                'file_ids' => [$url],
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'linkedDocument' => true,
                'documentId' => $document->id,
                'importedCount' => 1,
            ]);

        $this->assertCount(1, $document->fresh()->getMedia('additional_attachments'));
    }

    public function test_import_from_chat_with_required_category_marks_document_uploaded(): void
    {
        Storage::disk('public')->put('chat-imports/required/front.jpg', 'fake-content');
        $url = Storage::disk('public')->url('chat-imports/required/front.jpg');

        $type = $this->makeDocumentTypeWithRequirements(['dni_frontal']);
        [$conversation] = $this->makeConversation();
        $document = Document::create([
            'type_id' => $type->id,
            'customer_email' => 'cliente@example.com',
            'customer_firstname' => 'Test',
            'customer_lastname' => 'Cliente',
            'required_documents' => ['dni_frontal'],
        ]);
        $this->linkDocument($conversation, $document);
        $this->attachmentItem($conversation, [['url' => $url, 'name' => 'front.jpg', 'type' => 'image']]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.documents.import-from-chat', $conversation->id), [
                'file_ids' => [$url],
                'category' => 'dni_frontal',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'linkedDocument' => true,
                'documentId' => $document->id,
                'importedCount' => 1,
            ]);

        $fresh = $document->fresh();
        $this->assertCount(1, $fresh->getMedia('documents'));
        $this->assertSame('dni_frontal', $fresh->getMedia('documents')->first()->getCustomProperty('document_type'));
        $this->assertContains('dni_frontal', $fresh->uploaded_documents);
    }

    public function test_import_from_chat_targets_open_document_id_over_conversation_metadata(): void
    {
        Storage::disk('public')->put('chat-imports/open-doc/front.jpg', 'fake-content');
        $url = Storage::disk('public')->url('chat-imports/open-doc/front.jpg');

        $type = $this->makeDocumentTypeWithRequirements(['dni_frontal']);
        [$conversation] = $this->makeConversation();
        $metadataDocument = Document::create([
            'type_id' => $type->id,
            'customer_email' => 'cliente@example.com',
            'customer_firstname' => 'Meta',
            'customer_lastname' => 'Documento',
            'required_documents' => ['dni_frontal'],
        ]);
        $openDocument = Document::create([
            'type_id' => $type->id,
            'customer_email' => 'cliente@example.com',
            'customer_firstname' => 'Open',
            'customer_lastname' => 'Documento',
            'required_documents' => ['dni_frontal'],
        ]);
        $this->linkDocument($conversation, $metadataDocument);
        $this->attachmentItem($conversation, [['url' => $url, 'name' => 'front.jpg', 'type' => 'image']]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.documents.import-from-chat', $conversation->id), [
                'file_ids' => [$url],
                'category' => 'dni_frontal',
                'document_id' => $openDocument->id,
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'documentId' => $openDocument->id,
                'importedCount' => 1,
            ]);

        $this->assertCount(0, $metadataDocument->fresh()->getMedia('documents'));
        $this->assertCount(1, $openDocument->fresh()->getMedia('documents'));
        $this->assertContains('dni_frontal', $openDocument->fresh()->uploaded_documents);
    }

    public function test_import_from_chat_with_categories_map_assigns_each_file_to_its_own_document_type(): void
    {
        Storage::disk('public')->put('chat-imports/multi/trasera.jpg', 'fake-content');
        Storage::disk('public')->put('chat-imports/multi/contrato.jpg', 'fake-content');
        $urlA = Storage::disk('public')->url('chat-imports/multi/trasera.jpg');
        $urlB = Storage::disk('public')->url('chat-imports/multi/contrato.jpg');

        $type = $this->makeDocumentTypeWithRequirements(['dni_trasera', 'contrato_firmado']);
        [$conversation] = $this->makeConversation();
        $document = Document::create([
            'type_id' => $type->id,
            'customer_email' => 'cliente@example.com',
            'customer_firstname' => 'Test',
            'customer_lastname' => 'Cliente',
            'required_documents' => ['dni_trasera', 'contrato_firmado'],
        ]);
        $this->linkDocument($conversation, $document);
        $this->attachmentItem($conversation, [
            ['url' => $urlA, 'name' => 'trasera.jpg', 'type' => 'image'],
            ['url' => $urlB, 'name' => 'contrato.jpg', 'type' => 'image'],
        ]);

        // Antes de este fix, un lote con varios archivos siempre se
        // categorizaba entero con `category` (un solo valor): importar 2
        // imágenes destinadas a 2 tipos distintos etiquetaba ambas con el
        // mismo tipo y el segundo quedaba "faltante" para siempre.
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.documents.import-from-chat', $conversation->id), [
                'file_ids' => [$urlA, $urlB],
                'categories' => [$urlA => 'dni_trasera', $urlB => 'contrato_firmado'],
                'document_id' => $document->id,
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'importedCount' => 2]);

        $fresh = $document->fresh();
        $this->assertCount(2, $fresh->getMedia('documents'));
        $this->assertEqualsCanonicalizing(
            ['dni_trasera', 'contrato_firmado'],
            $fresh->getMedia('documents')->map(fn ($m) => $m->getCustomProperty('document_type'))->all()
        );
        $this->assertEmpty($fresh->getMissingDocuments());
    }

    public function test_import_from_chat_blocks_unsafe_external_url_via_ssrf_guard(): void
    {
        $unsafeUrl = 'http://localhost/definitely-not-a-local-file.jpg';

        [$conversation] = $this->makeConversation();
        $document = Document::create([
            'customer_email' => 'cliente@example.com',
            'customer_firstname' => 'Test',
            'customer_lastname' => 'Cliente',
        ]);
        $this->linkDocument($conversation, $document);
        $this->attachmentItem($conversation, [['url' => $unsafeUrl, 'name' => 'remote.jpg']]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.documents.import-from-chat', $conversation->id), [
                'file_ids' => [$unsafeUrl],
            ])
            ->assertStatus(422);

        $this->assertCount(0, $document->fresh()->getMedia('additional_attachments'));
    }

    // ── importFromDevice ────────────────────────────────────────────────

    public function test_user_without_permission_cannot_import_from_device(): void
    {
        $user = User::factory()->create();
        [$conversation] = $this->makeConversation();

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.conversations.documents.import-from-device', $conversation->id), [
                'files' => [UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf')],
            ])
            ->assertForbidden();
    }

    public function test_import_from_device_validation_rejects_disallowed_mime(): void
    {
        [$conversation] = $this->makeConversation();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.documents.import-from-device', $conversation->id), [
                'files' => [UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload')],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['files.0']);
    }

    public function test_import_from_device_validation_rejects_pdf(): void
    {
        [$conversation] = $this->makeConversation();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.documents.import-from-device', $conversation->id), [
                'files' => [UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf')],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['files.0']);
    }

    public function test_import_from_device_without_linked_document_auto_creates_expediente(): void
    {
        [$conversation] = $this->makeConversation();

        $response = $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.documents.import-from-device', $conversation->id), [
                'files' => [UploadedFile::fake()->image('doc.jpg')],
            ])
            ->assertOk();

        // El cliente tiene email → se auto-crea el expediente y el archivo se
        // adjunta a él (antes quedaba suelto en el disco público).
        $response->assertJson([
            'success' => true,
            'linkedDocument' => true,
            'importedCount' => 1,
        ]);

        $documentId = $response->json('documentId');
        $this->assertNotNull($documentId);
        $this->assertCount(1, Document::find($documentId)->getMedia('additional_attachments'));
        $this->assertSame($documentId, $conversation->fresh()->metadata['document_id']);
    }

    public function test_import_from_device_without_match_key_stores_on_public_disk(): void
    {
        $customer = Customer::factory()->create(['email' => '', 'phone' => null, 'whatsapp_phone' => null]);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);

        $response = $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.documents.import-from-device', $conversation->id), [
                'files' => [UploadedFile::fake()->image('doc.jpg')],
            ])
            ->assertOk();

        $response->assertJson([
            'success' => true,
            'linkedDocument' => false,
            'importedCount' => 1,
        ]);

        $this->assertNotEmpty(Storage::disk('public')->allFiles('chat-imports/'.$conversation->id));
        $imports = $conversation->fresh()->metadata['chat_document_imports'] ?? [];
        $this->assertCount(1, $imports);
    }

    public function test_import_from_device_attaches_file_to_linked_document(): void
    {
        [$conversation] = $this->makeConversation();
        $document = Document::create([
            'customer_email' => 'cliente@example.com',
            'customer_firstname' => 'Test',
            'customer_lastname' => 'Cliente',
        ]);
        $this->linkDocument($conversation, $document);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.documents.import-from-device', $conversation->id), [
                'files' => [UploadedFile::fake()->image('doc.jpg')],
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'linkedDocument' => true,
                'documentId' => $document->id,
                'importedCount' => 1,
            ]);

        $this->assertCount(1, $document->fresh()->getMedia('additional_attachments'));
    }

    public function test_import_from_device_with_required_category_marks_document_uploaded(): void
    {
        $type = $this->makeDocumentTypeWithRequirements(['dni_frontal']);
        [$conversation] = $this->makeConversation();
        $document = Document::create([
            'type_id' => $type->id,
            'customer_email' => 'cliente@example.com',
            'customer_firstname' => 'Test',
            'customer_lastname' => 'Cliente',
            'required_documents' => ['dni_frontal'],
        ]);
        $this->linkDocument($conversation, $document);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.documents.import-from-device', $conversation->id), [
                'files' => [UploadedFile::fake()->image('front.jpg')],
                'category' => 'dni_frontal',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'linkedDocument' => true,
                'documentId' => $document->id,
                'importedCount' => 1,
            ]);

        $fresh = $document->fresh();
        $this->assertCount(1, $fresh->getMedia('documents'));
        $this->assertSame('dni_frontal', $fresh->getMedia('documents')->first()->getCustomProperty('document_type'));
        $this->assertContains('dni_frontal', $fresh->uploaded_documents);
    }

    public function test_import_from_device_refreshes_panel_and_list_state_for_required_documents(): void
    {
        $type = $this->makeDocumentTypeWithRequirements([
            'dni_frontal',
            'dni_trasera',
            'contrato_firmado',
            'justificante_pago',
        ]);
        [$conversation] = $this->makeConversation();
        $document = Document::create([
            'type_id' => $type->id,
            'customer_email' => 'cliente@example.com',
            'customer_firstname' => 'Test',
            'customer_lastname' => 'Cliente',
            'required_documents' => ['dni_frontal', 'dni_trasera', 'contrato_firmado', 'justificante_pago'],
        ]);
        $this->linkDocument($conversation, $document);
        $this->attachRequiredImage($document, 'dni_frontal', 'dni_frontal.jpg');
        $this->attachRequiredImage($document, 'dni_trasera', 'dni_trasera.jpg');
        $document->syncUploadedDocumentsJson();

        $presenter = app(DocumentPanelPresenter::class);
        $before = $presenter->present($document->fresh());
        $this->assertCount(2, $before['files']);
        $this->assertCount(2, $before['missing']);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.documents.import-from-device', $conversation->id), [
                'files' => [UploadedFile::fake()->image('contrato.jpg')],
                'category' => 'contrato_firmado',
                'document_id' => $document->id,
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'documentId' => $document->id,
                'importedCount' => 1,
            ]);

        $fresh = $document->fresh();
        $detail = $presenter->present($fresh);
        $list = $presenter->list(collect([$fresh]))[0];

        $this->assertCount(3, $detail['files']);
        $this->assertCount(1, $detail['missing']);
        $this->assertSame(['justificante_pago'], array_column($detail['missing'], 'key'));
        $this->assertSame(3, $list['file_uploaded']);
        $this->assertSame(4, $list['file_total']);
        $this->assertSame(75, $list['progress_pct']);

        $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.conversations.documents.panel', [$conversation->id, $document->id]))
            ->assertOk()
            ->assertSee('3 de 4 completados')
            ->assertSee('Falta 1 documento requerido')
            ->assertDontSee('2 de 4 completados')
            ->assertDontSee('Faltan 2 documentos requeridos');
    }

    public function test_import_from_device_with_categories_array_assigns_each_file_to_its_own_document_type(): void
    {
        $type = $this->makeDocumentTypeWithRequirements(['dni_trasera', 'contrato_firmado']);
        [$conversation] = $this->makeConversation();
        $document = Document::create([
            'type_id' => $type->id,
            'customer_email' => 'cliente@example.com',
            'customer_firstname' => 'Test',
            'customer_lastname' => 'Cliente',
            'required_documents' => ['dni_trasera', 'contrato_firmado'],
        ]);
        $this->linkDocument($conversation, $document);

        // Antes de este fix, un lote con varios archivos siempre se
        // categorizaba entero con `category` (un solo valor): subir 2
        // archivos destinados a 2 tipos distintos etiquetaba ambos con el
        // mismo tipo y el segundo quedaba "faltante" para siempre.
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.documents.import-from-device', $conversation->id), [
                'files' => [
                    UploadedFile::fake()->image('trasera.jpg'),
                    UploadedFile::fake()->image('contrato.jpg'),
                ],
                'categories' => ['dni_trasera', 'contrato_firmado'],
                'document_id' => $document->id,
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'importedCount' => 2]);

        $fresh = $document->fresh();
        $this->assertCount(2, $fresh->getMedia('documents'));
        $this->assertEqualsCanonicalizing(
            ['dni_trasera', 'contrato_firmado'],
            $fresh->getMedia('documents')->map(fn ($m) => $m->getCustomProperty('document_type'))->all()
        );
        $this->assertEmpty($fresh->getMissingDocuments());
    }

    public function test_import_from_device_targets_open_document_id_over_conversation_metadata(): void
    {
        $type = $this->makeDocumentTypeWithRequirements(['dni_frontal']);
        [$conversation] = $this->makeConversation();
        $metadataDocument = Document::create([
            'type_id' => $type->id,
            'customer_email' => 'cliente@example.com',
            'customer_firstname' => 'Meta',
            'customer_lastname' => 'Documento',
            'required_documents' => ['dni_frontal'],
        ]);
        $openDocument = Document::create([
            'type_id' => $type->id,
            'customer_email' => 'cliente@example.com',
            'customer_firstname' => 'Open',
            'customer_lastname' => 'Documento',
            'required_documents' => ['dni_frontal'],
        ]);
        $this->linkDocument($conversation, $metadataDocument);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.documents.import-from-device', $conversation->id), [
                'files' => [UploadedFile::fake()->image('front.jpg')],
                'category' => 'dni_frontal',
                'document_id' => $openDocument->id,
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'documentId' => $openDocument->id,
                'importedCount' => 1,
            ]);

        $this->assertCount(0, $metadataDocument->fresh()->getMedia('documents'));
        $this->assertCount(1, $openDocument->fresh()->getMedia('documents'));
        $this->assertContains('dni_frontal', $openDocument->fresh()->uploaded_documents);
    }
}
