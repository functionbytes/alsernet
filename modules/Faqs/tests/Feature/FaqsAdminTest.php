<?php

namespace Modules\Faqs\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Faqs\Enums\FaqStatus;
use Modules\Faqs\Models\Faq;
use Modules\Faqs\Models\FaqCategory;
use Tests\TestCase;

class FaqsAdminTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo([
            'faqs.view',
            'faqs.create',
            'faqs.update',
            'faqs.delete',
            'faqs.categories.view',
            'faqs.categories.create',
            'faqs.categories.update',
            'faqs.categories.delete',
        ]);
    }

    public function test_faq_index_route_returns_ok(): void
    {
        $response = $this->actingAs($this->admin)->get(route('faqs.index'));

        $response->assertOk();
    }

    public function test_faq_create_route_returns_ok(): void
    {
        $response = $this->actingAs($this->admin)->get(route('faqs.create'));

        $response->assertOk();
    }

    public function test_faq_store_creates_faq(): void
    {
        $category = FaqCategory::factory()->create();
        $data = [
            'translations' => [
                ['locale' => 'es', 'question' => 'What is this?', 'answer' => 'This is a test.'],
            ],
            'category_id' => $category->id,
            'status' => FaqStatus::PUBLISHED->value,
        ];

        $response = $this->actingAs($this->admin)->post(route('faqs.store'), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('faqs', ['question' => 'What is this?']);
        $this->assertDatabaseHas('faq_translations', ['locale' => 'es', 'question' => 'What is this?']);
    }

    public function test_faq_edit_route_returns_ok(): void
    {
        $faq = Faq::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('faqs.edit', $faq));

        $response->assertOk();
    }

    public function test_faq_update_modifies_faq(): void
    {
        $faq = Faq::factory()->create(['question' => 'Old Question?']);

        $response = $this->actingAs($this->admin)->put(route('faqs.update', $faq), [
            'translations' => [
                ['locale' => 'es', 'question' => 'New Question?', 'answer' => $faq->answer],
            ],
            'category_id' => $faq->category_id,
            'status' => $faq->status->value,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('faqs', ['id' => $faq->id, 'question' => 'New Question?']);
        $this->assertDatabaseHas('faq_translations', ['faq_id' => $faq->id, 'locale' => 'es', 'question' => 'New Question?']);
    }

    public function test_faq_destroy_deletes_faq(): void
    {
        $faq = Faq::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('faqs.destroy', $faq));

        $response->assertRedirect();
        $this->assertDatabaseMissing('faqs', ['id' => $faq->id]);
    }

    public function test_category_index_route_returns_ok(): void
    {
        $response = $this->actingAs($this->admin)->get(route('faq-categories.index'));

        $response->assertOk();
    }

    public function test_category_store_creates_category(): void
    {
        $data = [
            'translations' => [
                ['locale' => 'es', 'name' => 'General', 'description' => 'Desc'],
            ],
            'order' => 1,
            'status' => FaqStatus::PUBLISHED->value,
        ];

        $response = $this->actingAs($this->admin)->post(route('faq-categories.store'), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('faq_categories', ['name' => 'General']);
        $this->assertDatabaseHas('faq_category_translations', ['locale' => 'es', 'name' => 'General']);
    }

    public function test_guest_cannot_access_admin_routes(): void
    {
        $response = $this->get(route('faqs.index'));

        $response->assertRedirect(route('login'));
    }
}
