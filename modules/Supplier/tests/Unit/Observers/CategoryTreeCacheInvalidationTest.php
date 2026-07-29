<?php

namespace Modules\Supplier\Tests\Unit\Observers;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Modules\Supplier\Models\Category\Category;
use Modules\Supplier\Models\Category\Sport;
use Tests\TestCase;

class CategoryTreeCacheInvalidationTest extends TestCase
{
    use DatabaseTransactions;

    private const SAMPLE_KEY = 'supplier:category_tree:active:plain';

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('supplier_sports') || ! Schema::hasTable('supplier_categories')) {
            $this->markTestSkipped('Category/Sport tables are not present in the test database.');
        }
    }

    public function test_creating_a_category_flushes_the_tree_cache(): void
    {
        Cache::put(self::SAMPLE_KEY, [['warmed' => true]], now()->addHour());
        $this->assertTrue(Cache::has(self::SAMPLE_KEY));

        Category::factory()->create();

        $this->assertFalse(Cache::has(self::SAMPLE_KEY));
    }

    public function test_updating_a_category_flushes_the_tree_cache(): void
    {
        $category = Category::factory()->create();

        Cache::put(self::SAMPLE_KEY, [['warmed' => true]], now()->addHour());

        $category->update(['name' => 'Updated name']);

        $this->assertFalse(Cache::has(self::SAMPLE_KEY));
    }

    public function test_deleting_a_category_flushes_the_tree_cache(): void
    {
        $category = Category::factory()->create();

        Cache::put(self::SAMPLE_KEY, [['warmed' => true]], now()->addHour());

        $category->delete();

        $this->assertFalse(Cache::has(self::SAMPLE_KEY));
    }

    public function test_changing_a_sport_flushes_the_tree_cache(): void
    {
        $sport = Sport::factory()->create();

        Cache::put(self::SAMPLE_KEY, [['warmed' => true]], now()->addHour());

        $sport->update(['name' => 'Updated sport']);

        $this->assertFalse(Cache::has(self::SAMPLE_KEY));
    }
}
