<?php

namespace Modules\Faqs\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Faqs\Enums\FaqStatus;
use Modules\Faqs\Models\Faq;
use Modules\Faqs\Models\FaqCategory;
use Tests\TestCase;

class FaqModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_faq_belongs_to_category(): void
    {
        $category = FaqCategory::factory()->create();
        $faq = Faq::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(FaqCategory::class, $faq->category);
        $this->assertTrue($faq->category->is($category));
    }

    public function test_category_has_many_faqs(): void
    {
        $category = FaqCategory::factory()->create();
        Faq::factory()->count(3)->create(['category_id' => $category->id]);

        $this->assertCount(3, $category->faqs);
    }

    public function test_faq_scope_active(): void
    {
        $published = Faq::factory()->create(['status' => FaqStatus::PUBLISHED]);
        Faq::factory()->create(['status' => FaqStatus::DRAFT]);

        $results = Faq::query()->active()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($published));
    }

    public function test_faq_scope_ordered(): void
    {
        $second = Faq::factory()->create(['order' => 2]);
        $first = Faq::factory()->create(['order' => 1]);

        $results = Faq::query()->ordered()->get();

        $this->assertTrue($results->first()->is($first));
        $this->assertTrue($results->last()->is($second));
    }

    public function test_category_scope_active(): void
    {
        $published = FaqCategory::factory()->create(['status' => FaqStatus::PUBLISHED]);
        FaqCategory::factory()->create(['status' => FaqStatus::DRAFT]);

        $results = FaqCategory::query()->active()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($published));
    }
}
