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

    public function test_faq_scope_published(): void
    {
        $published = Faq::factory()->create(['status' => FaqStatus::PUBLISHED]);
        Faq::factory()->create(['status' => FaqStatus::DRAFT]);

        $results = Faq::query()->published()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($published));
    }

    public function test_faq_trans_returns_translation(): void
    {
        $faq = Faq::factory()->create(['question' => 'Base Q', 'answer' => 'Base A']);
        $faq->translations()->create(['locale' => 'en', 'question' => 'English Q', 'answer' => 'English A']);

        $this->assertEquals('English Q', $faq->trans('question', 'en'));
        $this->assertEquals('English A', $faq->trans('answer', 'en'));
    }

    public function test_faq_trans_falls_back_to_base(): void
    {
        $faq = Faq::factory()->create(['question' => 'Base Q', 'answer' => 'Base A']);

        $this->assertEquals('Base Q', $faq->trans('question', 'fr'));
    }

    public function test_category_trans_returns_translation(): void
    {
        $category = FaqCategory::factory()->create(['name' => 'Base Name']);
        $category->translations()->create(['locale' => 'en', 'name' => 'English Name']);

        $this->assertEquals('English Name', $category->trans('name', 'en'));
    }

    public function test_category_trans_falls_back_to_base(): void
    {
        $category = FaqCategory::factory()->create(['name' => 'Base Name']);

        $this->assertEquals('Base Name', $category->trans('name', 'fr'));
    }
}
