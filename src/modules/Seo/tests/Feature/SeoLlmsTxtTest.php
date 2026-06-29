<?php

namespace Modules\Seo\Tests\Feature;

use Modules\Core\Models\Setting;
use Modules\Seo\Tests\TestCase;

class SeoLlmsTxtTest extends TestCase
{
    public function test_public_llms_txt_returns_plain_text(): void
    {
        $this->get('/llms.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=utf-8');
    }

    public function test_public_llms_txt_serves_default_content_when_unset(): void
    {
        $response = $this->get('/llms.txt');
        $response->assertOk();
        $this->assertStringContainsString('#', $response->getContent());
    }

    public function test_admin_edit_requires_permission(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('settings.seo.llms.edit'))
            ->assertForbidden();
    }

    public function test_admin_edit_returns_view(): void
    {
        $user = $this->createUser(['Seo.llms.index']);

        $this->actingAs($user)
            ->get(route('settings.seo.llms.edit'))
            ->assertOk()
            ->assertViewIs('Seo::settings.llms-txt.edit');
    }

    public function test_update_persists_content(): void
    {
        $user = $this->createUser(['Seo.llms.update']);
        $content = "# Test\n\n> desc\n\n## Links\n\n- [Home](/)";

        $this->actingAs($user)
            ->post(route('settings.seo.llms.update'), ['llms_txt' => $content])
            ->assertRedirect();

        $this->assertEquals(trim($content), Setting::get('seo.llms_txt'));
    }

    public function test_update_requires_update_permission(): void
    {
        $user = $this->createUser(['Seo.llms.index']);

        $this->actingAs($user)
            ->post(route('settings.seo.llms.update'), ['llms_txt' => 'hacked'])
            ->assertForbidden();
    }

    public function test_update_rejects_oversized_content(): void
    {
        $user = $this->createUser(['Seo.llms.update']);

        $this->actingAs($user)
            ->post(route('settings.seo.llms.update'), ['llms_txt' => str_repeat('a', 60000)])
            ->assertSessionHasErrors('llms_txt');
    }

    public function test_reset_clears_setting(): void
    {
        $user = $this->createUser(['Seo.llms.update']);
        Setting::set('seo.llms_txt', 'custom content');

        $this->actingAs($user)
            ->post(route('settings.seo.llms.reset'))
            ->assertRedirect();

        // After reset, the served content should be the default (not 'custom content')
        $served = $this->get('/llms.txt');
        $this->assertStringNotContainsString('custom content', $served->getContent());
    }
}
