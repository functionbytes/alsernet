<?php

namespace Modules\Supplier\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Modules\Supplier\Models\Prompt\Prompt;
use Modules\Supplier\Services\PromptSelectionService;
use Tests\TestCase;

class PromptSelectionServiceTest extends TestCase
{
    private PromptSelectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PromptSelectionService;
        Cache::flush();
    }

    public function test_service_class_is_instantiable(): void
    {
        $this->assertInstanceOf(PromptSelectionService::class, $this->service);
    }

    public function test_scope_priority_constant_is_ordered_correctly(): void
    {
        $reflection = new \ReflectionClassConstant($this->service, 'SCOPE_PRIORITY');
        $priority = $reflection->getValue();

        $this->assertSame(4, $priority['supplier_category']);
        $this->assertSame(3, $priority['supplier']);
        $this->assertSame(2, $priority['category']);
        $this->assertSame(1, $priority['global']);
    }

    public function test_cache_key_includes_all_parameters(): void
    {
        $key1 = $this->invokeCacheKey(1, 2, 'description', 3);
        $key2 = $this->invokeCacheKey(1, 2, 'title', 3);
        $key3 = $this->invokeCacheKey(1, 2, 'description', null);

        $this->assertNotSame($key1, $key2);
        $this->assertNotSame($key1, $key3);
    }

    public function test_cache_key_format_is_consistent(): void
    {
        $key = $this->invokeCacheKey(5, 10, 'description', 15);

        $this->assertStringStartsWith('prompt_selection:', $key);
        $this->assertStringContainsString('5', $key);
        $this->assertStringContainsString('10', $key);
        $this->assertStringContainsString('description', $key);
        $this->assertStringContainsString('15', $key);
    }

    public function test_explain_selection_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'explainSelection'));
    }

    public function test_render_prompt_replaces_at_brace_variables(): void
    {
        $prompt = new Prompt([
            'prompt_template' => 'Generate @{{ product_name }} for @{{ category }}',
        ]);

        $result = $this->service->renderPrompt($prompt, [
            'product_name' => 'Shoes',
            'category' => 'Sports',
        ]);

        $this->assertSame('Generate Shoes for Sports', $result);
    }

    public function test_render_prompt_replaces_brace_variables(): void
    {
        $prompt = new Prompt([
            'prompt_template' => 'Generate {{ product_name }} for {{ category }}',
        ]);

        $result = $this->service->renderPrompt($prompt, [
            'product_name' => 'Shoes',
            'category' => 'Sports',
        ]);

        $this->assertSame('Generate Shoes for Sports', $result);
    }

    private function invokeCacheKey(?int $supplierId, ?int $categoryId, string $contentType, ?int $sourceId): string
    {
        $reflection = new \ReflectionMethod($this->service, 'getCacheKey');
        $reflection->setAccessible(true);

        return $reflection->invoke($this->service, $supplierId, $categoryId, $contentType, $sourceId);
    }
}
