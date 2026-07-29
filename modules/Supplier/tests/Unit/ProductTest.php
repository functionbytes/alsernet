<?php

namespace Modules\Supplier\Tests\Unit;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Supplier\Models\Product\Product;
use Tests\TestCase;

class ProductTest extends TestCase
{
    public function test_scope_active_filters_correctly(): void
    {
        $query = Product::query()->active();
        $sql = $query->toSql();

        $this->assertStringContainsString('`available` = ?', $sql);
        $this->assertSame([true], $query->getBindings());
    }

    public function test_scope_default_filters_correctly(): void
    {
        $query = Product::query()->default();
        $sql = $query->toSql();

        $this->assertStringContainsString('`is_default` = ?', $sql);
        $this->assertSame([true], $query->getBindings());
    }

    public function test_scope_web_published_filters_correctly(): void
    {
        $query = Product::query()->webPublished();
        $sql = $query->toSql();

        $this->assertStringContainsString('`web_published` = ?', $sql);
        $this->assertSame([true], $query->getBindings());
    }

    public function test_scope_with_details_eager_loads_relations(): void
    {
        $query = Product::query()->withDetails();
        $eagerLoads = $query->getEagerLoads();

        $this->assertArrayHasKey('supplier', $eagerLoads);
        $this->assertArrayHasKey('category', $eagerLoads);
        $this->assertArrayHasKey('attributes', $eagerLoads);
    }

    public function test_scope_search_filters_name_and_code(): void
    {
        $query = Product::query()->search('test');
        $sql = $query->toSql();

        $this->assertStringContainsString('`name` LIKE ?', $sql);
        $this->assertStringContainsString('`code` LIKE ?', $sql);
    }

    public function test_scope_by_erp_id_filters_correctly(): void
    {
        $query = Product::query()->byErpId(456);
        $sql = $query->toSql();

        $this->assertStringContainsString('`erp_id` = ?', $sql);
        $this->assertSame([456], $query->getBindings());
    }

    public function test_casts_are_correct(): void
    {
        $product = new Product([
            'erp_id' => '456',
            'supplier_id' => '1',
            'category_id' => '2',
            'available' => '1',
            'is_default' => '0',
            'web_published' => '1',
        ]);

        $this->assertSame(456, $product->erp_id);
        $this->assertSame(1, $product->supplier_id);
        $this->assertSame(2, $product->category_id);
        $this->assertTrue($product->available);
        $this->assertFalse($product->is_default);
        $this->assertTrue($product->web_published);
    }

    public function test_metadata_cast_works_after_assignment(): void
    {
        $product = new Product;
        $product->metadata = ['color' => 'red'];

        $this->assertSame(['color' => 'red'], $product->metadata);
    }

    public function test_relationship_methods_exist(): void
    {
        $product = new Product;

        $this->assertInstanceOf(BelongsTo::class, $product->supplier());
        $this->assertInstanceOf(BelongsTo::class, $product->category());
        $this->assertInstanceOf(HasMany::class, $product->attributes());
        $this->assertInstanceOf(HasMany::class, $product->translations());
    }
}
