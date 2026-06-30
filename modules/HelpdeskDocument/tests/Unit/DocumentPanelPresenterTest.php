<?php

namespace Modules\HelpdeskDocument\Tests\Unit;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Document\Entities\Document;
use Modules\HelpdeskDocument\Services\DocumentPanelPresenter;
use PHPUnit\Framework\TestCase;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;

class DocumentPanelPresenterTest extends TestCase
{
    private DocumentPanelPresenter $presenter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->presenter = new DocumentPanelPresenter;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build a Document mock with controlled attributes and relations.
     *
     * Supported keys:
     *   id, uid, order_reference, type_label, type_description,
     *   status_key, status_label, required_documents (array), media_count (int),
     *   documentType (object|null), status (object|null), created_at (Carbon).
     *
     * Pass `documentType => null` or `status => null` to exercise null-safe paths.
     */
    private function makeDocument(array $attrs = []): Document
    {
        $mock = $this->getMockBuilder(Document::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getMedia'])
            ->getMock();

        $reflection = new \ReflectionClass($mock);

        // Scalar attributes go into Eloquent's internal $attributes array so that
        // __get → getAttribute works without a DB connection.
        $attributesProp = $reflection->getProperty('attributes');
        $attributesProp->setAccessible(true);
        $attributesProp->setValue($mock, [
            'id' => $attrs['id'] ?? 1,
            'uid' => $attrs['uid'] ?? 'test-uid-001',
            'order_reference' => $attrs['order_reference'] ?? 'ORD-001',
            // required_documents is cast as 'array' in Document; store as JSON.
            'required_documents' => isset($attrs['required_documents'])
                ? json_encode($attrs['required_documents'])
                : null,
            // Store a Carbon instance directly — safe under all Eloquent cast paths.
            'created_at' => $attrs['created_at'] ?? Carbon::parse('2025-01-01 12:00:00'),
        ]);

        // Relationships are stored in $relations so relationLoaded() returns true,
        // preventing Eloquent from attempting a live DB query.
        $relationsProp = $reflection->getProperty('relations');
        $relationsProp->setAccessible(true);

        $relations = [];

        if (array_key_exists('documentType', $attrs)) {
            $relations['documentType'] = $attrs['documentType'];
        } else {
            $dt = new \stdClass;
            $dt->label = $attrs['type_label'] ?? 'Documento de test';
            $dt->description = $attrs['type_description'] ?? 'Descripcion de test';
            $relations['documentType'] = $dt;
        }

        if (array_key_exists('status', $attrs)) {
            $relations['status'] = $attrs['status'];
        } else {
            $st = new \stdClass;
            $st->key = $attrs['status_key'] ?? 'pending';
            $st->label = $attrs['status_label'] ?? 'Pendiente';
            $relations['status'] = $st;
        }

        $relationsProp->setValue($mock, $relations);

        // getMedia must return a MediaCollection (declared return type enforced by PHPUnit 11).
        // Create a proper mock of MediaCollection and stub count() on it.
        $mediaCollection = $this->createMock(
            MediaCollection::class
        );
        $mediaCollection->method('count')->willReturn($attrs['media_count'] ?? 0);

        $mock->method('getMedia')
            ->with('documents')
            ->willReturn($mediaCollection);

        return $mock;
    }

    /** Convenience: build one document, run list(), return the single item. */
    private function listOne(array $attrs = []): array
    {
        $result = $this->presenter->list(new Collection([$this->makeDocument($attrs)]));

        return $result[0];
    }

    // -------------------------------------------------------------------------
    // Structure
    // -------------------------------------------------------------------------

    public function test_list_returns_array_with_correct_keys(): void
    {
        $item = $this->listOne();

        $expectedKeys = [
            'id', 'uid', 'order_reference', 'type_label',
            'status_key', 'status_label', 'file_count', 'created_human',
            'ago_human', 'group', 'description', 'file_total',
            'file_uploaded', 'progress_pct', 'progress_accent',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $item, "Expected key '{$key}' missing from list() item.");
        }
    }

    // -------------------------------------------------------------------------
    // Group mapping
    // -------------------------------------------------------------------------

    public function test_list_maps_pending_status_to_pending_group(): void
    {
        $item = $this->listOne(['status_key' => 'pending']);
        $this->assertSame('pending', $item['group']);
    }

    public function test_list_maps_approved_status_to_approved_group(): void
    {
        $item = $this->listOne(['status_key' => 'approved']);
        $this->assertSame('approved', $item['group']);
    }

    public function test_list_maps_completed_status_to_approved_group(): void
    {
        $item = $this->listOne(['status_key' => 'completed']);
        $this->assertSame('approved', $item['group']);
    }

    public function test_list_maps_rejected_status_to_rejected_group(): void
    {
        $item = $this->listOne(['status_key' => 'rejected']);
        $this->assertSame('rejected', $item['group']);
    }

    public function test_list_maps_cancelled_status_to_rejected_group(): void
    {
        $item = $this->listOne(['status_key' => 'cancelled']);
        $this->assertSame('rejected', $item['group']);
    }

    // -------------------------------------------------------------------------
    // Progress percentage
    // -------------------------------------------------------------------------

    public function test_progress_pct_is_zero_when_no_files_and_no_required(): void
    {
        $item = $this->listOne(['required_documents' => [], 'media_count' => 0]);
        $this->assertSame(0, $item['progress_pct']);
    }

    public function test_progress_pct_is_100_when_all_files_uploaded(): void
    {
        $item = $this->listOne([
            'required_documents' => ['dni', 'passport', 'payslip'],
            'media_count' => 3,
        ]);
        $this->assertSame(100, $item['progress_pct']);
    }

    public function test_progress_pct_is_correct_percentage(): void
    {
        $item = $this->listOne([
            'required_documents' => ['dni', 'passport', 'payslip', 'bank'],
            'media_count' => 2,
        ]);
        $this->assertSame(50, $item['progress_pct']);
    }

    // -------------------------------------------------------------------------
    // Progress accent colour
    // -------------------------------------------------------------------------

    public function test_progress_accent_is_green_for_approved_group(): void
    {
        $item = $this->listOne(['status_key' => 'approved']);
        $this->assertSame('#90bb13', $item['progress_accent']);
    }

    public function test_progress_accent_is_red_for_rejected_group(): void
    {
        $item = $this->listOne(['status_key' => 'rejected']);
        $this->assertSame('#FA896B', $item['progress_accent']);
    }

    public function test_progress_accent_is_amber_for_pending_group(): void
    {
        $item = $this->listOne(['status_key' => 'pending']);
        $this->assertSame('#FEC90F', $item['progress_accent']);
    }

    // -------------------------------------------------------------------------
    // Null-safe / graceful handling
    // -------------------------------------------------------------------------

    public function test_list_handles_null_document_type_gracefully(): void
    {
        $item = $this->listOne(['documentType' => null]);
        $this->assertSame('Documento', $item['type_label']);
        $this->assertSame('', $item['description']);
    }

    public function test_list_handles_null_status_gracefully(): void
    {
        $item = $this->listOne(['status' => null]);
        $this->assertSame('pending', $item['status_key']);
        $this->assertSame('pending', $item['group']);
        $this->assertSame('Pendiente', $item['status_label']);
    }

    // -------------------------------------------------------------------------
    // Collection size
    // -------------------------------------------------------------------------

    public function test_list_handles_empty_collection(): void
    {
        $result = $this->presenter->list(new Collection);
        $this->assertSame([], $result);
    }

    public function test_list_handles_multiple_documents(): void
    {
        $docs = new Collection([
            $this->makeDocument(['id' => 1, 'uid' => 'uid-1']),
            $this->makeDocument(['id' => 2, 'uid' => 'uid-2']),
            $this->makeDocument(['id' => 3, 'uid' => 'uid-3']),
        ]);

        $result = $this->presenter->list($docs);

        $this->assertCount(3, $result);
    }
}
