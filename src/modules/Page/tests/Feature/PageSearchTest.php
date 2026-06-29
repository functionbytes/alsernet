<?php

namespace Modules\Page\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Page\Enums\PageStatus;
use Modules\Page\Models\Page;
use Tests\TestCase;

class PageSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test pages
        Page::factory()->create([
            'title' => 'Laravel Framework Guide',
            'content' => 'This is a comprehensive guide about Laravel PHP framework.',
            'description' => 'Learn Laravel from scratch',
            'status' => PageStatus::Published->value,
            'published_at' => now()->subDay(),
        ]);

        Page::factory()->create([
            'title' => 'Vue.js Tutorial',
            'content' => 'A complete tutorial about Vue.js JavaScript framework.',
            'description' => 'Master Vue.js development',
            'status' => PageStatus::Published->value,
            'published_at' => now()->subDay(),
        ]);

        Page::factory()->create([
            'title' => 'Draft Page',
            'content' => 'This page is not published yet.',
            'status' => PageStatus::Draft->value,
        ]);
    }

    /** @test */
    public function it_can_search_pages_using_fulltext()
    {
        $results = Page::published()
            ->searchFullText('Laravel')
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Laravel Framework Guide', $results->first()->title);
    }

    /** @test */
    public function it_can_search_pages_using_like()
    {
        $results = Page::published()
            ->search('framework')
            ->get();

        $this->assertGreaterThanOrEqual(1, $results->count());
    }

    /** @test */
    public function it_only_searches_published_pages()
    {
        $results = Page::published()
            ->searchFullText('Draft')
            ->get();

        $this->assertCount(0, $results);
    }

    /** @test */
    public function search_api_endpoint_returns_results()
    {
        $response = $this->getJson('/api/v1/pages/search?q=Laravel');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'slug',
                        'excerpt',
                        'url',
                        'published_at',
                        'highlighted_title',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
                'query',
            ])
            ->assertJson([
                'success' => true,
                'query' => 'Laravel',
            ]);

        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    /** @test */
    public function quick_search_api_returns_limited_results()
    {
        $response = $this->getJson('/api/v1/pages/search/quick?q=framework');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'slug',
                        'url',
                        'highlighted_title',
                    ],
                ],
                'query',
            ]);

        $this->assertLessThanOrEqual(5, count($response->json('data')));
    }

    /** @test */
    public function search_validates_query_parameter()
    {
        $response = $this->getJson('/api/v1/pages/search');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['q']);
    }

    /** @test */
    public function search_highlights_matching_terms()
    {
        $response = $this->getJson('/api/v1/pages/search?q=Laravel');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        // Check if the title contains highlight markup
        $firstResult = $data[0];
        $this->assertStringContainsString('<mark>', $firstResult['highlighted_title']);
    }

    /** @test */
    public function combined_search_method_works()
    {
        $results = Page::searchPages('Laravel')->published()->get();

        $this->assertGreaterThanOrEqual(1, $results->count());
    }

    /** @test */
    public function public_index_supports_search()
    {
        $response = $this->get('/pages?search=Laravel');

        $response->assertStatus(200)
            ->assertSee('Laravel Framework Guide');
    }
}
