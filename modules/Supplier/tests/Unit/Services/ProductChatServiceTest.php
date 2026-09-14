<?php

namespace Modules\Supplier\Tests\Unit\Services;

use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Modules\Supplier\Database\Factories\Ai\AiBudgetFactory;
use Modules\Supplier\Database\Factories\Product\ProductFactory;
use Modules\Supplier\Services\ProductChatService;
use Tests\TestCase;

class ProductChatServiceTest extends TestCase
{
    use DatabaseTransactions;

    private ProductChatService $service;

    private function skipIfTablesMissing(): void
    {
        $required = ['suppliers', 'supplier_products'];

        foreach ($required as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Required table {$table} is not present in the test database.");
            }
        }
    }

    private function skipIfAiBudgetTableMissing(): void
    {
        if (! Schema::hasTable('supplier_ai_budgets')) {
            $this->markTestSkipped('Required table supplier_ai_budgets is not present in the test database.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        activity()->disableLogging();
        config(['services.openai.api_key' => 'test-key']);
        config(['services.anthropic.api_key' => '']);
        $this->service = new ProductChatService;
    }

    public function test_chat_returns_content_and_token_data_on_openai_success(): void
    {
        $this->skipIfAiBudgetTableMissing();

        Http::fake([
            'https://api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => 'This is the AI response.',
                        'annotations' => [],
                    ],
                ]],
                'usage' => ['prompt_tokens' => 50, 'completion_tokens' => 100],
            ], 200),
        ]);

        $messages = [
            ['role' => 'system', 'content' => 'You are a helpful assistant.'],
            ['role' => 'user', 'content' => 'Tell me about this product.'],
        ];

        $result = $this->service->chat($messages, 'gpt-4o-mini');

        $this->assertSame('This is the AI response.', $result['content']);
        $this->assertSame('gpt-4o-mini', $result['model']);
        $this->assertSame(150, $result['tokens']['total']);
        $this->assertIsFloat($result['cost']);
    }

    public function test_chat_throws_on_openai_api_error(): void
    {
        $this->skipIfAiBudgetTableMissing();

        Http::fake([
            'https://api.openai.com/*' => Http::response(['error' => 'Internal Server Error'], 500),
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Error de OpenAI/');

        $this->service->chat([
            ['role' => 'user', 'content' => 'Hello'],
        ], 'gpt-4o-mini');
    }

    public function test_chat_throws_for_unsupported_model(): void
    {
        // No DB table access for unsupported model — exception thrown before budget check
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/no soportado/');

        $this->service->chat([
            ['role' => 'user', 'content' => 'Hello'],
        ], 'unsupported-model-xyz');
    }

    public function test_chat_throws_when_budget_block_on_exceed_is_active(): void
    {
        if (! Schema::hasTable('supplier_ai_budgets')) {
            $this->markTestSkipped('Required table supplier_ai_budgets is not present in the test database.');
        }

        AiBudgetFactory::new()->create([
            'provider' => 'openai',
            'is_active' => true,
            'block_on_exceed' => true,
            'monthly_limit' => 0.001,
            'daily_limit' => null,
        ]);

        // Simulate that the budget is already exceeded by inserting costs
        // The budget check uses currentMonthUsage() which sums ai_costs
        // Insert a cost record that exceeds the limit
        if (Schema::hasTable('supplier_ai_costs')) {
            DB::table('supplier_ai_costs')->insert([
                'supplier_id' => null,
                'content_id' => null,
                'model' => 'gpt-4o-mini',
                'input_tokens' => 1000000,
                'output_tokens' => 1000000,
                'input_cost' => 0.5,
                'output_cost' => 0.5,
                'created_at' => now(),
            ]);
        }

        $this->expectException(Exception::class);

        $this->service->chat([
            ['role' => 'user', 'content' => 'Hello'],
        ], 'gpt-4o-mini');
    }

    public function test_build_product_context_does_not_include_other_product_data(): void
    {
        $this->skipIfTablesMissing();
        $this->skipIfAiBudgetTableMissing();

        $product1 = ProductFactory::new()->create(['name' => 'Product Alpha']);
        $product2 = ProductFactory::new()->create(['name' => 'Product Beta']);

        $context = $this->service->buildProductContext($product1->load(['supplier', 'attributes', 'translations', 'category']));

        $this->assertStringContainsString('Product Alpha', $context);
        $this->assertStringNotContainsString('Product Beta', $context);
    }

    public function test_build_product_variables_returns_expected_keys(): void
    {
        $this->skipIfTablesMissing();
        $this->skipIfAiBudgetTableMissing();

        $product = ProductFactory::new()->create();
        $product->load(['supplier', 'attributes', 'translations', 'category']);

        $vars = $this->service->buildProductVariables($product);

        $this->assertArrayHasKey('product_name', $vars);
        $this->assertArrayHasKey('product_code', $vars);
        $this->assertArrayHasKey('supplier', $vars);
        $this->assertArrayHasKey('category', $vars);
        $this->assertArrayHasKey('attributes', $vars);
    }
}
