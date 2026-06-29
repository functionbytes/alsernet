<?php

namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Database\Factories\GlobalOptionFactory;
use Modules\Ecommerce\Database\Factories\GlobalOptionValueFactory;
use Modules\Ecommerce\Database\Factories\ProductLabelFactory;
use Modules\Ecommerce\Database\Factories\SpecificationAttributeFactory;
use Modules\Ecommerce\Database\Factories\SpecificationGroupFactory;
use Modules\Ecommerce\Database\Factories\SpecificationTableFactory;
use Modules\Ecommerce\Models\GlobalOption;
use Modules\Ecommerce\Models\GlobalOptionValue;
use Modules\Ecommerce\Models\ProductAttributeSet;
use Modules\Ecommerce\Models\ProductLabel;
use Modules\Ecommerce\Models\SpecificationAttribute;
use Modules\Ecommerce\Models\SpecificationGroup;
use Modules\Ecommerce\Models\SpecificationTable;
use Tests\TestCase;

class EcommerceAdminCatalogTest extends TestCase
{
    // ─── Routes ───────────────────────────────────────────────────────────────

    public function test_product_label_routes_are_registered(): void
    {
        $names = collect(Route::getRoutes()->getRoutesByName())->keys();

        $this->assertTrue($names->contains('ecommerce.product-labels.index'));
        $this->assertTrue($names->contains('ecommerce.product-labels.create'));
        $this->assertTrue($names->contains('ecommerce.product-labels.store'));
        $this->assertTrue($names->contains('ecommerce.product-labels.edit'));
        $this->assertTrue($names->contains('ecommerce.product-labels.update'));
        $this->assertTrue($names->contains('ecommerce.product-labels.destroy'));
    }

    public function test_global_option_routes_are_registered(): void
    {
        $names = collect(Route::getRoutes()->getRoutesByName())->keys();

        $this->assertTrue($names->contains('ecommerce.global-options.index'));
        $this->assertTrue($names->contains('ecommerce.global-options.create'));
        $this->assertTrue($names->contains('ecommerce.global-options.store'));
        $this->assertTrue($names->contains('ecommerce.global-options.edit'));
        $this->assertTrue($names->contains('ecommerce.global-options.update'));
        $this->assertTrue($names->contains('ecommerce.global-options.destroy'));
    }

    public function test_attribute_set_routes_are_registered(): void
    {
        $names = collect(Route::getRoutes()->getRoutesByName())->keys();

        $this->assertTrue($names->contains('ecommerce.product-attribute-sets.index'));
        $this->assertTrue($names->contains('ecommerce.product-attribute-sets.create'));
        $this->assertTrue($names->contains('ecommerce.product-attribute-sets.store'));
        $this->assertTrue($names->contains('ecommerce.product-attribute-sets.edit'));
        $this->assertTrue($names->contains('ecommerce.product-attribute-sets.update'));
        $this->assertTrue($names->contains('ecommerce.product-attribute-sets.destroy'));
    }

    public function test_specification_group_routes_are_registered(): void
    {
        $names = collect(Route::getRoutes()->getRoutesByName())->keys();

        $this->assertTrue($names->contains('ecommerce.specification-groups.index'));
        $this->assertTrue($names->contains('ecommerce.specification-groups.create'));
        $this->assertTrue($names->contains('ecommerce.specification-groups.store'));
        $this->assertTrue($names->contains('ecommerce.specification-groups.edit'));
        $this->assertTrue($names->contains('ecommerce.specification-groups.update'));
        $this->assertTrue($names->contains('ecommerce.specification-groups.destroy'));
    }

    public function test_specification_attribute_routes_are_registered(): void
    {
        $names = collect(Route::getRoutes()->getRoutesByName())->keys();

        $this->assertTrue($names->contains('ecommerce.specification-attributes.index'));
        $this->assertTrue($names->contains('ecommerce.specification-attributes.create'));
        $this->assertTrue($names->contains('ecommerce.specification-attributes.store'));
        $this->assertTrue($names->contains('ecommerce.specification-attributes.edit'));
        $this->assertTrue($names->contains('ecommerce.specification-attributes.update'));
        $this->assertTrue($names->contains('ecommerce.specification-attributes.destroy'));
    }

    public function test_specification_table_routes_are_registered(): void
    {
        $names = collect(Route::getRoutes()->getRoutesByName())->keys();

        $this->assertTrue($names->contains('ecommerce.specification-tables.index'));
        $this->assertTrue($names->contains('ecommerce.specification-tables.create'));
        $this->assertTrue($names->contains('ecommerce.specification-tables.store'));
        $this->assertTrue($names->contains('ecommerce.specification-tables.edit'));
        $this->assertTrue($names->contains('ecommerce.specification-tables.update'));
        $this->assertTrue($names->contains('ecommerce.specification-tables.destroy'));
    }

    // ─── Models ───────────────────────────────────────────────────────────────

    public function test_all_catalog_models_exist(): void
    {
        $this->assertTrue(class_exists(ProductLabel::class));
        $this->assertTrue(class_exists(GlobalOption::class));
        $this->assertTrue(class_exists(GlobalOptionValue::class));
        $this->assertTrue(class_exists(ProductAttributeSet::class));
        $this->assertTrue(class_exists(SpecificationGroup::class));
        $this->assertTrue(class_exists(SpecificationAttribute::class));
        $this->assertTrue(class_exists(SpecificationTable::class));
    }

    public function test_product_attribute_set_has_correct_fillable(): void
    {
        $fillable = (new ProductAttributeSet)->getFillable();

        $this->assertContains('is_searchable', $fillable);
        $this->assertContains('is_comparable', $fillable);
        $this->assertContains('is_use_in_product_listing', $fillable);
        $this->assertNotContains('use_in_listing', $fillable);
    }

    public function test_product_attribute_set_casts_booleans(): void
    {
        $model = new ProductAttributeSet;
        $casts = $model->getCasts();

        $this->assertArrayHasKey('is_searchable', $casts);
        $this->assertArrayHasKey('is_comparable', $casts);
        $this->assertArrayHasKey('is_use_in_product_listing', $casts);
    }

    public function test_specification_attribute_casts_options_as_array(): void
    {
        $model = new SpecificationAttribute;
        $casts = $model->getCasts();

        $this->assertArrayHasKey('options', $casts);
        $this->assertSame('array', $casts['options']);
    }

    public function test_specification_attribute_fillable_uses_group_id(): void
    {
        $fillable = (new SpecificationAttribute)->getFillable();

        $this->assertContains('group_id', $fillable);
        $this->assertNotContains('specification_group_id', $fillable);
    }

    public function test_global_option_values_relationship_ordered(): void
    {
        $sql = GlobalOption::query()->with('values')->toRawSql();

        // Verify the relationship orders by 'order'
        $option = new GlobalOption;
        $query = $option->values()->getQuery()->toRawSql();
        $this->assertStringContainsString('order', strtolower($query));
    }

    // ─── Factories ────────────────────────────────────────────────────────────

    public function test_all_catalog_factories_exist(): void
    {
        $this->assertTrue(class_exists(ProductLabelFactory::class));
        $this->assertTrue(class_exists(GlobalOptionFactory::class));
        $this->assertTrue(class_exists(GlobalOptionValueFactory::class));
        $this->assertTrue(class_exists(SpecificationGroupFactory::class));
        $this->assertTrue(class_exists(SpecificationAttributeFactory::class));
        $this->assertTrue(class_exists(SpecificationTableFactory::class));
    }

    // ─── Views ────────────────────────────────────────────────────────────────

    public function test_all_catalog_views_resolve(): void
    {
        $views = [
            'ecommerce::product-labels.index',
            'ecommerce::product-labels.create',
            'ecommerce::product-labels.edit',
            'ecommerce::global-options.index',
            'ecommerce::global-options.create',
            'ecommerce::global-options.edit',
            'ecommerce::attribute-sets.index',
            'ecommerce::attribute-sets.create',
            'ecommerce::attribute-sets.edit',
            'ecommerce::specification-groups.index',
            'ecommerce::specification-groups.create',
            'ecommerce::specification-groups.edit',
            'ecommerce::specification-attributes.index',
            'ecommerce::specification-attributes.create',
            'ecommerce::specification-attributes.edit',
            'ecommerce::specification-tables.index',
            'ecommerce::specification-tables.create',
            'ecommerce::specification-tables.edit',
        ];

        foreach ($views as $view) {
            $this->assertTrue(view()->exists($view), "View [{$view}] not found.");
        }
    }
}
