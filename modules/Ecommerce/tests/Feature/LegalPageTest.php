<?php

namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ecommerce\Models\LegalPage;
use Tests\TestCase;

class LegalPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitor_can_view_published_legal_page(): void
    {
        LegalPage::query()->create([
            'slug' => 'privacy-test',
            'title' => 'Política de privacidad',
            'content' => '<p>Test content unique abc123</p>',
            'is_published' => true,
        ]);

        $response = $this->get(route('shop.legal.show', 'privacy-test'));

        $response->assertOk();
        $response->assertSee('Política de privacidad');
        $response->assertSee('Test content unique abc123', false);
    }

    public function test_unpublished_page_returns_404(): void
    {
        LegalPage::query()->create([
            'slug' => 'draft-test',
            'title' => 'Draft',
            'content' => 'Hidden',
            'is_published' => false,
        ]);

        $response = $this->get(route('shop.legal.show', 'draft-test'));
        $response->assertNotFound();
    }

    public function test_nonexistent_slug_returns_404(): void
    {
        $response = $this->get(route('shop.legal.show', 'does-not-exist-xyz'));
        $response->assertNotFound();
    }
}
