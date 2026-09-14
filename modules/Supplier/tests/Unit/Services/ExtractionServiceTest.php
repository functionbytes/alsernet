<?php

namespace Modules\Supplier\Tests\Unit\Services;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Modules\Supplier\Database\Factories\Extraction\ExtractionBatchFactory;
use Modules\Supplier\Database\Factories\Source\SourceFactory;
use Modules\Supplier\Database\Factories\Supplier\SupplierFactory;
use Modules\Supplier\Models\Extraction\ExtractionMapping;
use Modules\Supplier\Models\Extraction\ExtractionResult;
use Modules\Supplier\Models\Source\Source;
use Modules\Supplier\Services\ExtractionService;
use Tests\TestCase;

class ExtractionServiceTest extends TestCase
{
    use DatabaseTransactions;

    private ExtractionService $service;

    private function skipIfTablesMissing(): void
    {
        $required = [
            'supplier_extraction_batches',
            'supplier_extraction_mappings',
            'supplier_extraction_results',
        ];

        foreach ($required as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Required table {$table} is not present in the test database.");
            }
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        activity()->disableLogging();
        $this->service = new ExtractionService;
    }

    public function test_process_extraction_result_creates_extraction_result_record(): void
    {
        $this->skipIfTablesMissing();

        $supplier = SupplierFactory::new()->create();
        $source = SourceFactory::new()->create([
            'supplier_id' => $supplier->id,
            'extraction_mode' => Source::EXTRACTION_MODE_AI,
        ]);
        $batch = ExtractionBatchFactory::new()->create([
            'supplier_id' => $supplier->id,
            'source_id' => $source->id,
            'status' => 'processing',
        ]);

        $data = [
            'reference' => 'PROD-001',
            'name' => 'Test Product',
            'price' => 29.99,
            'product_name' => 'Test Product',
            'short_description' => 'A fine product.',
        ];

        $result = $this->service->processExtractionResult($data, $batch);

        $this->assertInstanceOf(ExtractionResult::class, $result);
        $this->assertSame('PROD-001', $result->reference);
        $this->assertSame($supplier->id, $result->supplier_id);
        $this->assertDatabaseHas('supplier_extraction_results', [
            'id' => $result->id,
            'reference' => 'PROD-001',
        ]);
    }

    public function test_process_extraction_result_marks_new_when_no_prior_result(): void
    {
        $this->skipIfTablesMissing();

        $supplier = SupplierFactory::new()->create();
        $source = SourceFactory::new()->create([
            'supplier_id' => $supplier->id,
            'extraction_mode' => Source::EXTRACTION_MODE_AI,
        ]);
        $batch = ExtractionBatchFactory::new()->create([
            'supplier_id' => $supplier->id,
            'source_id' => $source->id,
            'status' => 'processing',
        ]);

        $result = $this->service->processExtractionResult([
            'reference' => 'UNIQUE-'.uniqid(),
            'name' => 'Brand New Product',
            'price' => 10.00,
        ], $batch);

        $this->assertSame('new', $result->status);
    }

    public function test_generate_content_hash_returns_same_hash_for_identical_stable_fields(): void
    {
        $data = [
            'product_name' => 'Widget',
            'short_description' => 'A widget.',
            'price' => 9.99,   // volatile — excluded from hash
            'stock' => 5,      // volatile — excluded from hash
        ];

        $hash1 = $this->service->generateContentHash($data);

        // Changing volatile fields should not affect the hash
        $data['price'] = 0.01;
        $data['stock'] = 999;
        $hash2 = $this->service->generateContentHash($data);

        $this->assertSame($hash1, $hash2);
    }

    public function test_generate_content_hash_differs_when_stable_fields_change(): void
    {
        $base = ['product_name' => 'Widget', 'short_description' => 'A widget.'];
        $changed = ['product_name' => 'Gadget', 'short_description' => 'A gadget.'];

        $this->assertNotSame(
            $this->service->generateContentHash($base),
            $this->service->generateContentHash($changed)
        );
    }

    public function test_detect_changes_returns_new_when_no_existing_result(): void
    {
        $status = $this->service->detectChanges(['product_name' => 'Widget'], null);

        $this->assertSame('new', $status);
    }

    public function test_complete_extraction_marks_batch_completed(): void
    {
        $this->skipIfTablesMissing();

        $supplier = SupplierFactory::new()->create();
        $source = SourceFactory::new()->create(['supplier_id' => $supplier->id]);
        $batch = ExtractionBatchFactory::new()->create([
            'supplier_id' => $supplier->id,
            'source_id' => $source->id,
            'status' => 'processing',
            'total_items' => 0,
            'failed_items' => 0,
        ]);

        $this->service->completeExtraction($batch);

        $batch->refresh();
        $this->assertSame('completed', $batch->status);
        $this->assertNotNull($batch->completed_at);
    }

    public function test_apply_mapping_maps_source_fields_to_target_fields(): void
    {
        $mapping = new ExtractionMapping([
            'field_mappings' => [
                'product_name' => 'titulo',
                'price' => ['source' => 'precio', 'default' => 0],
            ],
            'validation_rules' => [],
        ]);

        $rawData = ['titulo' => 'Camisa', 'precio' => 29.99];

        $mapped = $this->service->applyMapping($rawData, $mapping);

        $this->assertSame('Camisa', $mapped['product_name']);
        $this->assertSame(29.99, $mapped['price']);
    }

    public function test_apply_mapping_uses_default_when_source_field_absent(): void
    {
        $mapping = new ExtractionMapping([
            'field_mappings' => [
                'price' => ['source' => 'precio', 'default' => 0.0],
            ],
            'validation_rules' => [],
        ]);

        $mapped = $this->service->applyMapping([], $mapping);

        $this->assertEquals(0.0, $mapped['price']);
    }
}
