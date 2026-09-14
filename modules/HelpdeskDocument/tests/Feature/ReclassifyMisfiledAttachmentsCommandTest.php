<?php

namespace Modules\HelpdeskDocument\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentRequirement;
use Modules\Document\Entities\DocumentType;
use Modules\Helpdesk\Tests\HelpdeskTestCase;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Covers helpdeskdocument:reclassify-attachments — the backfill for expedientes
 * that had files land in additional_attachments before ChatGalleryDocumentController
 * used Document::getRequiredDocuments() (previously it compared against the
 * denormalized `required_documents` column, which could be empty/stale).
 */
class ReclassifyMisfiledAttachmentsCommandTest extends HelpdeskTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function makeDocumentTypeWithRequirements(array $requirements): DocumentType
    {
        $type = DocumentType::create([
            'slug' => 'codex-reclassify-'.uniqid(),
            'label' => 'Codex reclasificar',
            'description' => 'Tipo documental para pruebas del backfill',
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

    private function makeDocument(DocumentType $type): Document
    {
        return Document::create([
            'type_id' => $type->id,
            'customer_email' => 'cliente@example.com',
            'customer_firstname' => 'Test',
            'customer_lastname' => 'Cliente',
        ]);
    }

    private function attachAsAdditional(Document $document, string $uploadType, string $name): Media
    {
        return $document->addMedia(UploadedFile::fake()->image($name))
            ->usingFileName($name)
            ->withCustomProperties(['upload_type' => $uploadType])
            ->toMediaCollection('additional_attachments');
    }

    public function test_dry_run_reports_candidates_without_modifying_anything(): void
    {
        $type = $this->makeDocumentTypeWithRequirements(['dni_frontal', 'contrato_firmado']);
        $document = $this->makeDocument($type);
        $this->attachAsAdditional($document, 'dni_frontal', 'front.jpg');

        $this->artisan('helpdeskdocument:reclassify-attachments', ['--document' => $document->id])
            ->assertSuccessful();

        $fresh = $document->fresh();
        $this->assertCount(1, $fresh->getMedia('additional_attachments'));
        $this->assertCount(0, $fresh->getMedia('documents'));
    }

    public function test_apply_moves_misfiled_attachment_into_documents_collection(): void
    {
        $type = $this->makeDocumentTypeWithRequirements(['dni_frontal', 'contrato_firmado']);
        $document = $this->makeDocument($type);
        $this->attachAsAdditional($document, 'dni_frontal', 'front.jpg');

        $this->artisan('helpdeskdocument:reclassify-attachments', [
            '--document' => $document->id,
            '--apply' => true,
        ])->assertSuccessful();

        $fresh = $document->fresh();
        $this->assertCount(1, $fresh->getMedia('documents'));
        $this->assertSame('dni_frontal', $fresh->getMedia('documents')->first()->getCustomProperty('document_type'));
        $this->assertContains('dni_frontal', $fresh->uploaded_documents);
        $this->assertCount(0, $fresh->getMedia('additional_attachments'));
    }

    public function test_apply_skips_type_already_fulfilled_in_documents_collection(): void
    {
        $type = $this->makeDocumentTypeWithRequirements(['dni_frontal']);
        $document = $this->makeDocument($type);

        $document->addMedia(UploadedFile::fake()->image('real.jpg'))
            ->usingFileName('real.jpg')
            ->withCustomProperties(['document_type' => 'dni_frontal'])
            ->toMediaCollection('documents');

        $this->attachAsAdditional($document, 'dni_frontal', 'dup.jpg');

        $this->artisan('helpdeskdocument:reclassify-attachments', [
            '--document' => $document->id,
            '--apply' => true,
        ])->assertSuccessful();

        $fresh = $document->fresh();
        $this->assertCount(1, $fresh->getMedia('documents'));
        $this->assertCount(1, $fresh->getMedia('additional_attachments'));
    }

    public function test_apply_only_reclassifies_first_duplicate_per_type(): void
    {
        $type = $this->makeDocumentTypeWithRequirements(['dni_frontal']);
        $document = $this->makeDocument($type);
        $this->attachAsAdditional($document, 'dni_frontal', 'a.jpg');
        $this->attachAsAdditional($document, 'dni_frontal', 'b.jpg');

        $this->artisan('helpdeskdocument:reclassify-attachments', [
            '--document' => $document->id,
            '--apply' => true,
        ])->assertSuccessful();

        $fresh = $document->fresh();
        $this->assertCount(1, $fresh->getMedia('documents'));
        $this->assertCount(1, $fresh->getMedia('additional_attachments'));
    }

    public function test_ignores_attachments_without_a_matching_required_type(): void
    {
        $type = $this->makeDocumentTypeWithRequirements(['dni_frontal']);
        $document = $this->makeDocument($type);
        $this->attachAsAdditional($document, 'other', 'random.jpg');

        $this->artisan('helpdeskdocument:reclassify-attachments', [
            '--document' => $document->id,
            '--apply' => true,
        ])->assertSuccessful();

        $fresh = $document->fresh();
        $this->assertCount(0, $fresh->getMedia('documents'));
        $this->assertCount(1, $fresh->getMedia('additional_attachments'));
    }
}
