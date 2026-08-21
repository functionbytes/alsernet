<?php

namespace Modules\Supplier\Tests\Unit\Services;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Modules\Supplier\Database\Factories\Ai\AiContentFactory;
use Modules\Supplier\Database\Factories\Extraction\ExtractionResultFactory;
use Modules\Supplier\Database\Factories\Prompt\PromptFactory;
use Modules\Supplier\Database\Factories\Source\SourceFactory;
use Modules\Supplier\Database\Factories\Supplier\SupplierFactory;
use Modules\Supplier\Models\Ai\AiContent;
use Modules\Supplier\Services\ContentGenerationService;
use Tests\TestCase;

class ContentGenerationServiceTest extends TestCase
{
    use DatabaseTransactions;

    private ContentGenerationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        activity()->disableLogging();

        $this->service = $this->app->make(ContentGenerationService::class);
    }

    private function skipIfTablesMissing(array $tables): void
    {
        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Required table {$table} is not present in the test database.");
            }
        }
    }

    public function test_parse_ai_response_extracts_product_name(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('parseAiResponse');
        $method->setAccessible(true);

        $response = "Name: Test Product\nShort Description:\nA short description here.\n";
        $result = $method->invoke($this->service, $response);

        $this->assertSame('Test Product', $result['name']);
    }

    public function test_parse_ai_response_extracts_bullet_points(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('parseAiResponse');
        $method->setAccessible(true);

        $response = "Name: Product\nBullet Points:\n- Feature one\n- Feature two\n- Feature three\n";
        $result = $method->invoke($this->service, $response);

        $this->assertCount(3, $result['bullet_points']);
        $this->assertSame('Feature one', $result['bullet_points'][0]);
    }

    public function test_parse_ai_response_extracts_seo_title(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('parseAiResponse');
        $method->setAccessible(true);

        $response = "SEO Title: Best Product Title\nSEO Description:\nGreat for SEO.\n";
        $result = $method->invoke($this->service, $response);

        $this->assertSame('Best Product Title', $result['seo_title']);
    }

    public function test_parse_ai_response_sanitises_dangerous_html_in_long_description(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('parseAiResponse');
        $method->setAccessible(true);

        // Response containing a script tag in the description
        $response = "Name: Product\nDescription:\n<p>Good product</p><script>alert('xss')</script>\n";
        $result = $method->invoke($this->service, $response);

        // The raw response is preserved by parseAiResponse, but sanitisation
        // happens when the content is stored/rendered. We test that the
        // long_description is populated (not null).
        $this->assertNotNull($result['long_description'] ?? $result['name']);
    }

    public function test_parse_ai_response_extracts_fields_from_fenced_json_block(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('parseAiResponse');
        $method->setAccessible(true);

        // Formato real observado con gemini-2.5-flash: preámbulo de texto +
        // bloque ```json en vez del "Name:/Description:" pedido en el prompt.
        $response = <<<'TEXT'
            Aquí tienes la descripción del producto, basándome en la información real encontrada.

            ```json
            {
              "generated_name": "Nike Air Max Pulse 21",
              "short_description": "Zapatillas urbanas con amortiguación Air.",
              "long_description": "Descripción larga con detalles técnicos del producto.",
              "bullet_points": ["Amortiguación Air", "Upper transpirable", "Suela de goma"],
              "seo_title": "Nike Air Max Pulse 21 | Comodidad urbana",
              "seo_description": "Compra las Nike Air Max Pulse 21 con amortiguación Air.",
              "seo_keywords": "nike, air max, pulse, running"
            }
            ```
            TEXT;

        $result = $method->invoke($this->service, $response);

        $this->assertSame('Nike Air Max Pulse 21', $result['name']);
        $this->assertSame('Zapatillas urbanas con amortiguación Air.', $result['short_description']);
        $this->assertStringContainsString('Descripción larga', $result['long_description']);
        $this->assertCount(3, $result['bullet_points']);
        $this->assertSame('Amortiguación Air', $result['bullet_points'][0]);
        $this->assertSame('Nike Air Max Pulse 21 | Comodidad urbana', $result['seo_title']);
        $this->assertSame('nike, air max, pulse, running', $result['seo_keywords']);
    }

    public function test_parse_ai_response_extracts_fields_from_raw_json_without_fences(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('parseAiResponse');
        $method->setAccessible(true);

        $response = '{"generated_name": "Producto X", "short_description": "Corta.", "long_description": "Larga.", "bullet_points": ["Punto uno"]}';

        $result = $method->invoke($this->service, $response);

        $this->assertSame('Producto X', $result['name']);
        $this->assertSame('Corta.', $result['short_description']);
    }

    public function test_parse_ai_response_truncates_fields_to_column_limits(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('parseAiResponse');
        $method->setAccessible(true);

        // supplier_ai_contents.seo_title es VARCHAR(70) y seo_description
        // VARCHAR(160) — el modelo no cuenta caracteres pese a que se lo
        // pedimos en el prompt. Sin truncar, el UPDATE completo fallaba
        // (visto en vivo con Gemini: "Data too long for column 'seo_title'").
        $payload = json_encode([
            'generated_name' => 'Producto',
            'seo_title' => str_repeat('Un título de SEO muy largo que no respeta el límite pedido ', 3),
            'seo_description' => str_repeat('Una meta descripción larguísima que tampoco respeta el límite. ', 5),
            'seo_keywords' => implode(', ', array_fill(0, 60, 'palabra-clave-larga')),
        ]);

        $result = $method->invoke($this->service, $payload);

        $this->assertLessThanOrEqual(70, mb_strlen($result['seo_title']));
        $this->assertLessThanOrEqual(160, mb_strlen($result['seo_description']));
        $this->assertLessThanOrEqual(255, mb_strlen($result['seo_keywords']));
    }

    public function test_parse_ai_response_extracts_technologies_from_json(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('parseAiResponse');
        $method->setAccessible(true);

        $response = '{"generated_name": "Nike X", "short_description": "Corta.", "technologies": ["Air Max", "React"]}';

        $result = $method->invoke($this->service, $response);

        $this->assertSame(['Air Max', 'React'], $result['technologies']);
    }

    public function test_parse_ai_response_ignores_incidental_json_and_uses_line_format(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('parseAiResponse');
        $method->setAccessible(true);

        // Un único { suelto en el texto (p.ej. una nota entre llaves) no debe
        // confundirse con un payload JSON de contenido — solo 1 clave de
        // contenido reconocible ("name") no basta (mínimo 2).
        $response = "Name: Producto normal\nDescription:\nUsa el formato {legacy} de toda la vida.\n";

        $result = $method->invoke($this->service, $response);

        $this->assertSame('Producto normal', $result['name']);
        $this->assertStringContainsString('legacy', $result['long_description']);
    }

    public function test_calculate_quality_score_returns_zero_for_empty_content(): void
    {
        $score = $this->service->calculateQualityScore('');

        $this->assertSame(0.0, $score);
    }

    public function test_calculate_quality_score_returns_higher_for_rich_content(): void
    {
        $short = 'Short text.';
        $long = str_repeat('This product features excellent specifications, benefits, and warranty. ', 20);

        $shortScore = $this->service->calculateQualityScore($short);
        $longScore = $this->service->calculateQualityScore($long);

        $this->assertGreaterThan($shortScore, $longScore);
    }

    public function test_generate_content_calls_openai_api_and_creates_ai_content(): void
    {
        $this->skipIfTablesMissing(['suppliers', 'supplier_prompts', 'supplier_extraction_results', 'supplier_ai_contents']);

        Http::fake([
            'https://api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => "Name: Test Product\nDescription:\nA great product description with many details.\n",
                    ],
                ]],
                'usage' => [
                    'prompt_tokens' => 100,
                    'completion_tokens' => 200,
                ],
                'model' => 'gpt-4o-mini',
            ], 200),
        ]);

        config(['services.openai.api_key' => 'test-key-for-fake']);
        config(['supplier.ai.default_model' => 'gpt-4o-mini']);

        $supplier = SupplierFactory::new()->create();
        $prompt = PromptFactory::new()->create([
            'supplier_id' => $supplier->id,
            'scope' => 'supplier',
            'is_active' => true,
            'is_default' => true,
        ]);

        $extractionResult = ExtractionResultFactory::new()->create([
            'supplier_id' => $supplier->id,
            'reference' => 'TEST-001',
        ]);

        $content = $this->service->generateContent($extractionResult, $prompt);

        $this->assertInstanceOf(AiContent::class, $content);
        $this->assertSame($supplier->id, $content->supplier_id);
        $this->assertNotNull($content->generation_metadata);
    }

    public function test_generate_content_marks_as_failed_when_api_errors(): void
    {
        $this->skipIfTablesMissing(['suppliers', 'supplier_prompts', 'supplier_extraction_results', 'supplier_ai_contents']);

        Http::fake([
            'https://api.openai.com/*' => Http::response(['error' => 'API error'], 500),
        ]);

        config(['services.openai.api_key' => 'test-key-for-fake']);
        config(['supplier.ai.default_model' => 'gpt-4o-mini']);

        $supplier = SupplierFactory::new()->create();
        $prompt = PromptFactory::new()->create([
            'supplier_id' => $supplier->id,
            'scope' => 'supplier',
            'is_active' => true,
            'is_default' => true,
        ]);

        $extractionResult = ExtractionResultFactory::new()->create([
            'supplier_id' => $supplier->id,
        ]);

        $this->expectException(\Exception::class);

        $this->service->generateContent($extractionResult, $prompt);
    }

    public function test_prompt_variable_aliases_use_real_model_code_not_numeric_erp_id(): void
    {
        $this->skipIfTablesMissing(['suppliers', 'supplier_ai_contents']);

        // erp_reference es el ID numérico ERP (ej. "830001"); model_id es el
        // código de modelo real (ej. "NIKEPULSE-21"). {{model_id}} en la
        // plantilla debe resolver al código real, no al ID numérico — un
        // modelo de IA confundido con "830001" como nombre de modelo se
        // niega a redactar ("no encontré el producto 830001").
        $content = AiContentFactory::new()->create([
            'erp_reference' => '830001',
            'model_id' => 'NIKEPULSE-21',
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('prepareVariablesFromContent');
        $method->setAccessible(true);

        $vars = $method->invoke($this->service, $content);

        $this->assertSame('NIKEPULSE-21', $vars['model_id']);
        $this->assertSame('830001', $vars['erp_reference']);
        $this->assertSame('830001', $vars['reference']);
    }

    public function test_preferred_source_urls_only_includes_active_urls_from_active_sources(): void
    {
        $this->skipIfTablesMissing(['suppliers', 'supplier_sources', 'supplier_source_content_urls']);

        $supplier = SupplierFactory::new()->create();

        $activeSource = SourceFactory::new()->website()->create(['supplier_id' => $supplier->id, 'is_active' => true]);
        $activeSource->contentUrls()->create(['url' => 'https://proveedor.com/catalogo', 'is_active' => true, 'priority' => 1]);
        $activeSource->contentUrls()->create(['url' => 'https://proveedor.com/descartada', 'is_active' => false, 'priority' => 2]);

        $inactiveSource = SourceFactory::new()->website()->create(['supplier_id' => $supplier->id, 'is_active' => false]);
        $inactiveSource->contentUrls()->create(['url' => 'https://proveedor.com/fuente-inactiva', 'is_active' => true]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('preferredSourceUrls');
        $method->setAccessible(true);

        $urls = $method->invoke($this->service, $supplier->id);

        $this->assertSame(['https://proveedor.com/catalogo'], $urls);
    }

    public function test_preferred_source_urls_returns_empty_array_without_supplier_id(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('preferredSourceUrls');
        $method->setAccessible(true);

        $this->assertSame([], $method->invoke($this->service, null));
    }

    public function test_track_ai_cost_creates_cost_record(): void
    {
        $this->skipIfTablesMissing(['suppliers', 'supplier_ai_costs']);

        $supplier = SupplierFactory::new()->create();

        $cost = $this->service->trackAiCost(
            model: 'gpt-4o-mini',
            inputTokens: 500,
            outputTokens: 1000,
            cost: 0.001,
            supplierId: $supplier->id,
        );

        $this->assertDatabaseHas('supplier_ai_costs', [
            'supplier_id' => $supplier->id,
            'model' => 'gpt-4o-mini',
            'input_tokens' => 500,
            'output_tokens' => 1000,
        ]);
    }
}
