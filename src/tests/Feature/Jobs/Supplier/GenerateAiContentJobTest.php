<?php

namespace Tests\Feature\Jobs\Supplier;

use App\Jobs\Supplier\GenerateAiContentJob;
use App\Models\Supplier\Supplier;
use App\Models\Supplier\SupplierAiContent;
use App\Models\Supplier\SupplierExtractionMapping;
use App\Models\Supplier\SupplierExtractionResult;
use App\Models\Supplier\SupplierPrompt;
use App\Models\Supplier\SupplierSource;
use App\Services\Supplier\ContentGenerationService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GenerateAiContentJobTest extends TestCase
{
    use RefreshDatabase;

    protected Supplier $supplier;

    protected SupplierSource $source;

    protected SupplierExtractionMapping $mapping;

    protected SupplierPrompt $prompt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->supplier = Supplier::factory()->create([
            'label' => 'Test Supplier',
            'is_active' => true,
        ]);

        $this->source = SupplierSource::factory()->create([
            'supplier_id' => $this->supplier->id,
            'label' => 'Test Source',
            'source_type' => 'api',
            'is_active' => true,
        ]);

        $this->mapping = SupplierExtractionMapping::factory()->create([
            'supplier_id' => $this->supplier->id,
            'source_id' => $this->source->id,
        ]);

        $this->prompt = SupplierPrompt::factory()->create([
            'supplier_id' => $this->supplier->id,
            'scope' => 'supplier',
            'label' => 'Test Prompt',
            'prompt_template' => 'Generate product description for {{product_name}}',
            'is_active' => true,
            'is_default' => true,
        ]);

        config([
            'supplier.ai.rate_limits' => [
                'minute' => 100,
                'hour' => 1000,
                'day' => 10000,
            ],
            'supplier.ai.daily_budget_limit' => 1000.00,
            'supplier.ai.monthly_budget_limit' => 10000.00,
        ]);
    }

    public function test_job_can_be_constructed_with_single_result(): void
    {
        $result = SupplierExtractionResult::factory()->create([
            'supplier_id' => $this->supplier->id,
            'source_id' => $this->source->id,
            'mapping_id' => $this->mapping->id,
        ]);

        $job = new GenerateAiContentJob($result);

        $this->assertInstanceOf(GenerateAiContentJob::class, $job);
    }

    public function test_job_can_be_constructed_with_result_id(): void
    {
        $result = SupplierExtractionResult::factory()->create([
            'supplier_id' => $this->supplier->id,
            'source_id' => $this->source->id,
            'mapping_id' => $this->mapping->id,
        ]);

        $job = new GenerateAiContentJob($result->id);

        $this->assertInstanceOf(GenerateAiContentJob::class, $job);
    }

    public function test_job_can_be_constructed_with_array_of_results(): void
    {
        $results = SupplierExtractionResult::factory()->count(3)->create([
            'supplier_id' => $this->supplier->id,
            'source_id' => $this->source->id,
            'mapping_id' => $this->mapping->id,
        ]);

        $job = new GenerateAiContentJob($results->toArray());

        $this->assertInstanceOf(GenerateAiContentJob::class, $job);
    }

    public function test_job_can_be_constructed_with_array_of_result_ids(): void
    {
        $results = SupplierExtractionResult::factory()->count(3)->create([
            'supplier_id' => $this->supplier->id,
            'source_id' => $this->source->id,
            'mapping_id' => $this->mapping->id,
        ]);

        $job = new GenerateAiContentJob($results->pluck('id')->toArray());

        $this->assertInstanceOf(GenerateAiContentJob::class, $job);
    }

    public function test_job_processes_single_result_successfully(): void
    {
        $result = SupplierExtractionResult::factory()->create([
            'supplier_id' => $this->supplier->id,
            'source_id' => $this->source->id,
            'mapping_id' => $this->mapping->id,
            'extracted_data' => [
                'product_name' => 'Test Product',
                'short_description' => 'Short desc',
                'price' => 99.99,
            ],
        ]);

        $mockService = $this->createMock(ContentGenerationService::class);
        $mockService->expects($this->once())
            ->method('generateContent')
            ->with($result, $this->prompt)
            ->willReturn(SupplierAiContent::factory()->create([
                'supplier_id' => $this->supplier->id,
                'status' => SupplierAiContent::STATUS_PENDING_VALIDATION,
                'generation_metadata' => [
                    'model' => 'gpt-4o-mini',
                    'tokens' => ['input' => 100, 'output' => 200, 'total' => 300],
                    'cost' => 0.05,
                    'latency_ms' => 1500,
                ],
            ]));

        $job = new GenerateAiContentJob($result, $this->prompt->id);
        $job->handle($mockService);

        $this->assertTrue(true);
    }

    public function test_job_handles_batch_processing(): void
    {
        $results = SupplierExtractionResult::factory()->count(5)->create([
            'supplier_id' => $this->supplier->id,
            'source_id' => $this->source->id,
            'mapping_id' => $this->mapping->id,
            'extracted_data' => [
                'product_name' => 'Test Product',
                'short_description' => 'Short desc',
            ],
        ]);

        $mockService = $this->createMock(ContentGenerationService::class);
        $mockService->expects($this->exactly(5))
            ->method('generateContent')
            ->willReturn(SupplierAiContent::factory()->create([
                'supplier_id' => $this->supplier->id,
                'status' => SupplierAiContent::STATUS_PENDING_VALIDATION,
                'generation_metadata' => [
                    'model' => 'gpt-4o-mini',
                    'tokens' => ['input' => 100, 'output' => 200, 'total' => 300],
                    'cost' => 0.05,
                    'latency_ms' => 1500,
                ],
            ]));

        $job = new GenerateAiContentJob($results->toArray(), $this->prompt->id);
        $job->handle($mockService);

        $this->assertTrue(true);
    }

    public function test_job_respects_rate_limits(): void
    {
        config(['supplier.ai.rate_limits.minute' => 0]);

        $result = SupplierExtractionResult::factory()->create([
            'supplier_id' => $this->supplier->id,
            'source_id' => $this->source->id,
            'mapping_id' => $this->mapping->id,
        ]);

        $mockService = $this->createMock(ContentGenerationService::class);
        $mockService->expects($this->never())
            ->method('generateContent');

        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->once()->withArgs(function ($message, $context) {
            return str_contains($message, 'Rate limit exceeded');
        });

        $job = new GenerateAiContentJob($result);

        try {
            $job->handle($mockService);
        } catch (Exception $e) {
        }

        $this->assertTrue(true);
    }

    public function test_job_tracks_generation_metrics(): void
    {
        $result = SupplierExtractionResult::factory()->create([
            'supplier_id' => $this->supplier->id,
            'source_id' => $this->source->id,
            'mapping_id' => $this->mapping->id,
            'extracted_data' => [
                'product_name' => 'Test Product',
            ],
        ]);

        $content = SupplierAiContent::factory()->create([
            'supplier_id' => $this->supplier->id,
            'status' => SupplierAiContent::STATUS_PENDING_VALIDATION,
            'generation_metadata' => [
                'model' => 'gpt-4o-mini',
                'tokens' => ['input' => 150, 'output' => 250, 'total' => 400],
                'cost' => 0.08,
                'latency_ms' => 2000,
            ],
        ]);

        $mockService = $this->createMock(ContentGenerationService::class);
        $mockService->method('generateContent')
            ->willReturn($content);

        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('debug')->once()->withArgs(function ($message, $context) {
            return str_contains($message, 'Generation metrics tracked')
                && isset($context['total_tokens'])
                && $context['total_tokens'] === 400;
        });

        $job = new GenerateAiContentJob($result);
        $job->handle($mockService);

        $this->assertTrue(true);
    }

    public function test_job_handles_generation_failure_gracefully(): void
    {
        $result = SupplierExtractionResult::factory()->create([
            'supplier_id' => $this->supplier->id,
            'source_id' => $this->source->id,
            'mapping_id' => $this->mapping->id,
        ]);

        $mockService = $this->createMock(ContentGenerationService::class);
        $mockService->method('generateContent')
            ->willThrowException(new Exception('AI API failed'));

        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('error')->once()->withArgs(function ($message, $context) {
            return str_contains($message, 'AI content generation failed');
        });

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('AI API failed');

        $job = new GenerateAiContentJob($result);
        $job->handle($mockService);
    }

    public function test_job_continues_batch_processing_after_single_failure(): void
    {
        $results = SupplierExtractionResult::factory()->count(3)->create([
            'supplier_id' => $this->supplier->id,
            'source_id' => $this->source->id,
            'mapping_id' => $this->mapping->id,
        ]);

        $mockService = $this->createMock(ContentGenerationService::class);
        $mockService->expects($this->exactly(3))
            ->method('generateContent')
            ->willReturnOnConsecutiveCalls(
                SupplierAiContent::factory()->create(['supplier_id' => $this->supplier->id]),
                $this->throwException(new Exception('Failed')),
                SupplierAiContent::factory()->create(['supplier_id' => $this->supplier->id])
            );

        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('error')->once();

        $job = new GenerateAiContentJob($results->toArray());
        $job->handle($mockService);

        $this->assertTrue(true);
    }

    public function test_job_has_proper_retry_configuration(): void
    {
        $result = SupplierExtractionResult::factory()->create([
            'supplier_id' => $this->supplier->id,
            'source_id' => $this->source->id,
            'mapping_id' => $this->mapping->id,
        ]);

        $job = new GenerateAiContentJob($result);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(300, $job->timeout);
        $this->assertEquals(3, $job->maxExceptions);
        $this->assertTrue($job->failOnTimeout);
    }

    public function test_job_increases_timeout_for_batch_processing(): void
    {
        $results = SupplierExtractionResult::factory()->count(5)->create([
            'supplier_id' => $this->supplier->id,
            'source_id' => $this->source->id,
            'mapping_id' => $this->mapping->id,
        ]);

        $job = new GenerateAiContentJob($results->toArray());

        $this->assertEquals(600, $job->timeout);
    }

    public function test_job_generates_unique_batch_id(): void
    {
        $result = SupplierExtractionResult::factory()->create([
            'supplier_id' => $this->supplier->id,
            'source_id' => $this->source->id,
            'mapping_id' => $this->mapping->id,
        ]);

        $job1 = new GenerateAiContentJob($result);
        $job2 = new GenerateAiContentJob($result);

        $this->assertNotEquals($job1->uniqueId(), $job2->uniqueId());
    }

    public function test_job_can_be_dispatched_to_queue(): void
    {
        Queue::fake();

        $result = SupplierExtractionResult::factory()->create([
            'supplier_id' => $this->supplier->id,
            'source_id' => $this->source->id,
            'mapping_id' => $this->mapping->id,
        ]);

        GenerateAiContentJob::dispatch($result);

        Queue::assertPushed(GenerateAiContentJob::class);
    }

    public function test_failed_method_updates_content_status(): void
    {
        $result = SupplierExtractionResult::factory()->create([
            'supplier_id' => $this->supplier->id,
            'source_id' => $this->source->id,
            'mapping_id' => $this->mapping->id,
            'reference' => 'TEST-REF-001',
        ]);

        $content = SupplierAiContent::factory()->create([
            'supplier_id' => $this->supplier->id,
            'erp_reference' => 'TEST-REF-001',
            'status' => SupplierAiContent::STATUS_GENERATING,
        ]);

        $job = new GenerateAiContentJob($result);
        $job->failed(new Exception('Test failure'));

        $content->refresh();
        $this->assertTrue($content->hasErrors());
    }
}
