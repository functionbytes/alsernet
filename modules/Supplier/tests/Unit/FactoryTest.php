<?php

namespace Modules\Supplier\Tests\Unit;

use Modules\Supplier\Database\Factories\Ai\AiBudgetFactory;
use Modules\Supplier\Database\Factories\Ai\AiContentFactory;
use Modules\Supplier\Database\Factories\Category\CategoryFactory;
use Modules\Supplier\Database\Factories\Category\SportFactory;
use Modules\Supplier\Database\Factories\Product\ProductFactory;
use Modules\Supplier\Database\Factories\Prompt\PromptFactory;
use Modules\Supplier\Database\Factories\Source\SourceFactory;
use Modules\Supplier\Database\Factories\Supplier\SupplierFactory;
use Tests\TestCase;

class FactoryTest extends TestCase
{
    public function test_supplier_factory_generates_valid_data(): void
    {
        $data = SupplierFactory::new()->definition();

        $this->assertArrayHasKey('label', $data);
        $this->assertArrayHasKey('code', $data);
        $this->assertArrayHasKey('available', $data);
        $this->assertTrue($data['available']);
    }

    public function test_product_factory_generates_valid_data(): void
    {
        $data = ProductFactory::new()->definition();

        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('erp_id', $data);
        $this->assertArrayHasKey('available', $data);
    }

    public function test_category_factory_generates_valid_data(): void
    {
        $data = CategoryFactory::new()->definition();

        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('available', $data);
    }

    public function test_sport_factory_generates_valid_data(): void
    {
        $data = SportFactory::new()->definition();

        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('available', $data);
    }

    public function test_source_factory_generates_valid_data(): void
    {
        $data = SourceFactory::new()->definition();

        $this->assertArrayHasKey('label', $data);
        $this->assertArrayHasKey('source_type', $data);
        $this->assertContains($data['source_type'], ['website', 'ftp', 'file', 'api']);
    }

    public function test_prompt_factory_generates_valid_data(): void
    {
        $data = PromptFactory::new()->definition();

        $this->assertArrayHasKey('label', $data);
        $this->assertArrayHasKey('prompt_template', $data);
        $this->assertArrayHasKey('scope', $data);
    }

    public function test_ai_budget_factory_generates_valid_data(): void
    {
        $data = AiBudgetFactory::new()->definition();

        $this->assertArrayHasKey('provider', $data);
        $this->assertArrayHasKey('monthly_limit', $data);
        $this->assertArrayHasKey('is_active', $data);
    }

    public function test_ai_content_factory_generates_valid_data(): void
    {
        $data = AiContentFactory::new()->definition();

        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('uid', $data);
    }

    public function test_factory_states_return_correct_values(): void
    {
        $supplierData = SupplierFactory::new()->inactive()->raw();
        $this->assertFalse($supplierData['available']);

        $sourceData = SourceFactory::new()->highTrust()->raw();
        $this->assertSame('high', $sourceData['trust_level']);

        $promptData = PromptFactory::new()->template()->raw();
        $this->assertTrue($promptData['is_template']);
    }
}
