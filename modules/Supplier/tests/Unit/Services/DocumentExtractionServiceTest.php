<?php

namespace Modules\Supplier\Tests\Unit\Services;

use Illuminate\Support\Facades\Http;
use Modules\Supplier\Services\DocumentExtractionService;
use Tests\TestCase;

class DocumentExtractionServiceTest extends TestCase
{
    private DocumentExtractionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.openai.api_key' => 'test-key-fake']);

        $this->service = $this->app->make(DocumentExtractionService::class);
    }

    public function test_returns_empty_array_when_api_key_missing(): void
    {
        config(['services.openai.api_key' => null]);
        $service = $this->app->make(DocumentExtractionService::class);

        $result = $service->extractProductsFromHtml('<html><body>Product</body></html>');

        $this->assertSame([], $result);
    }

    public function test_extract_products_from_structured_data_returns_empty_for_empty_rows(): void
    {
        $this->assertSame([], $this->service->extractProductsFromStructuredData([]));
    }

    public function test_extract_products_from_html_calls_openai_and_parses_array(): void
    {
        $payload = [
            ['reference' => 'REF-1', 'name' => 'Product 1', 'price' => 19.99],
            ['reference' => 'REF-2', 'name' => 'Product 2', 'price' => 29.99],
        ];

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => ['content' => json_encode($payload)],
                ]],
                'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 50],
                'model' => 'gpt-4o-mini',
            ], 200),
        ]);

        $result = $this->service->extractProductsFromHtml(
            '<html><body><div class="product">Product 1</div></body></html>',
            'https://supplier.example/products'
        );

        $this->assertCount(2, $result);
        $this->assertSame('REF-1', $result[0]['reference']);
    }

    public function test_extract_products_from_text_works_with_pdf_doc_type(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => ['content' => json_encode([['reference' => 'TXT-1']])],
                ]],
                'usage' => ['prompt_tokens' => 50, 'completion_tokens' => 20],
                'model' => 'gpt-4o-mini',
            ], 200),
        ]);

        $result = $this->service->extractProductsFromText('Product list as PDF', 'pdf');

        $this->assertCount(1, $result);
    }

    public function test_extract_products_from_structured_data_serialises_rows(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => ['content' => json_encode([['reference' => 'XLS-1']])],
                ]],
                'usage' => ['prompt_tokens' => 30, 'completion_tokens' => 10],
                'model' => 'gpt-4o-mini',
            ], 200),
        ]);

        $result = $this->service->extractProductsFromStructuredData([
            ['REF-A', 'Product A', 12.5],
            ['REF-B', 'Product B', 25.0],
        ], 'excel');

        $this->assertCount(1, $result);
        $this->assertSame('XLS-1', $result[0]['reference']);
    }

    public function test_extract_products_from_html_returns_empty_on_invalid_json_response(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => ['content' => 'not json at all'],
                ]],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5],
                'model' => 'gpt-4o-mini',
            ], 200),
        ]);

        $result = $this->service->extractProductsFromHtml('<html></html>');

        $this->assertSame([], $result);
    }

    public function test_extract_products_from_html_returns_empty_on_api_error(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response(['error' => 'rate limited'], 429),
        ]);

        $result = $this->service->extractProductsFromHtml('<html></html>');

        $this->assertSame([], $result);
    }
}
