<?php

namespace Modules\Faqs\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Faqs\Enums\FaqStatus;
use Modules\Faqs\Models\Faq;
use Modules\Faqs\Models\FaqCategory;
use Tests\TestCase;

class FaqPublicTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_index_returns_ok(): void
    {
        $category = FaqCategory::factory()->create(['status' => FaqStatus::PUBLISHED]);
        Faq::factory()->create([
            'category_id' => $category->id,
            'status' => FaqStatus::PUBLISHED,
        ]);

        $response = $this->get(route('faqs.public.index'));

        $response->assertOk();
    }

    public function test_public_index_shows_only_published(): void
    {
        $category = FaqCategory::factory()->create(['status' => FaqStatus::PUBLISHED]);
        $published = Faq::factory()->create([
            'category_id' => $category->id,
            'status' => FaqStatus::PUBLISHED,
            'question' => 'Published Question?',
        ]);
        Faq::factory()->create([
            'category_id' => $category->id,
            'status' => FaqStatus::DRAFT,
            'question' => 'Draft Question?',
        ]);

        $response = $this->get(route('faqs.public.index'));

        $response->assertOk();
        $response->assertSee('Published Question?');
        $response->assertDontSee('Draft Question?');
    }
}
