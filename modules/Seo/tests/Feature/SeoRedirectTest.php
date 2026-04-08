<?php

namespace Modules\Seo\Tests\Feature;

use Modules\Core\Models\Setting;
use Modules\Seo\Models\SeoRedirect;
use Modules\Seo\Tests\TestCase;

class SeoRedirectTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Authentication guard
    // -------------------------------------------------------------------------

    public function test_redirect_list_requires_auth(): void
    {
        $this->get(route('setting.seo.redirects.index'))
            ->assertRedirect(route('login'));
    }

    public function test_create_form_requires_auth(): void
    {
        $this->get(route('setting.seo.redirects.create'))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // Create redirect — happy path
    // -------------------------------------------------------------------------

    public function test_can_create_redirect(): void
    {
        $user = $this->createUser([
            'Seo.redirects.index',
            'Seo.redirects.create',
        ]);

        $this->actingAs($user)
            ->post(route('setting.seo.redirects.store'), [
                'source_path' => '/old-page',
                'target_path' => '/new-page',
                'status_code' => 301,
            ])
            ->assertRedirect(route('setting.seo.redirects.index'));

        $this->assertDatabaseHas('seo_redirects', [
            'source_path' => '/old-page',
            'target_path' => '/new-page',
            'status_code' => 301,
        ]);
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    public function test_redirect_requires_valid_source(): void
    {
        $user = $this->createUser([
            'Seo.redirects.index',
            'Seo.redirects.create',
        ]);

        $this->actingAs($user)
            ->post(route('setting.seo.redirects.store'), [
                'source_path' => '',
                'target_path' => '/destination',
                'status_code' => 301,
            ])
            ->assertSessionHasErrors(['source_path']);

        $this->assertDatabaseEmpty('seo_redirects');
    }

    public function test_redirect_requires_valid_status_code(): void
    {
        $user = $this->createUser([
            'Seo.redirects.index',
            'Seo.redirects.create',
        ]);

        $this->actingAs($user)
            ->post(route('setting.seo.redirects.store'), [
                'source_path' => '/some-page',
                'target_path' => '/destination',
                'status_code' => 200,
            ])
            ->assertSessionHasErrors(['status_code']);
    }

    public function test_redirect_source_must_be_unique(): void
    {
        SeoRedirect::create([
            'source_path' => '/existing-path',
            'target_path' => '/somewhere',
            'status_code' => 301,
        ]);

        $user = $this->createUser([
            'Seo.redirects.index',
            'Seo.redirects.create',
        ]);

        $this->actingAs($user)
            ->post(route('setting.seo.redirects.store'), [
                'source_path' => '/existing-path',
                'target_path' => '/other-destination',
                'status_code' => 302,
            ])
            ->assertSessionHasErrors(['source_path']);

        $this->assertDatabaseCount('seo_redirects', 1);
    }

    // -------------------------------------------------------------------------
    // Detect chains endpoint
    // -------------------------------------------------------------------------

    public function test_detect_chains_requires_auth(): void
    {
        $this->get(route('setting.seo.redirects.detect-chains'))
            ->assertRedirect(route('login'));
    }

    public function test_detect_chains_returns_json(): void
    {
        $user = $this->createUser(['Seo.redirects.index']);

        $response = $this->actingAs($user)
            ->getJson(route('setting.seo.redirects.detect-chains'));

        $response->assertOk()
            ->assertJsonStructure(['chains', 'count']);
    }

    // -------------------------------------------------------------------------
    // Chain detection warning on save
    // -------------------------------------------------------------------------

    public function test_chain_detection_warns_on_save(): void
    {
        // B -> C already exists
        SeoRedirect::create([
            'source_path' => '/page-b',
            'target_path' => '/page-c',
            'status_code' => 301,
            'is_active' => true,
        ]);

        $user = $this->createUser([
            'Seo.redirects.index',
            'Seo.redirects.create',
        ]);

        // Now create A -> B, which forms the chain A -> B -> C
        $this->actingAs($user)
            ->post(route('setting.seo.redirects.store'), [
                'source_path' => '/page-a',
                'target_path' => '/page-b',
                'status_code' => 301,
            ])
            ->assertRedirect(route('setting.seo.redirects.index'))
            ->assertSessionHas('warning');
    }

    // -------------------------------------------------------------------------
    // Robots.txt
    // -------------------------------------------------------------------------

    public function test_robots_txt_edit_requires_auth(): void
    {
        $this->get(route('setting.seo.robots.edit'))
            ->assertRedirect(route('login'));
    }

    public function test_robots_txt_can_be_edited(): void
    {
        $user = $this->createUser();

        $newContent = "User-agent: *\nDisallow: /admin\n";

        $this->actingAs($user)
            ->post(route('setting.seo.robots.update'), [
                'robots_txt' => $newContent,
            ])
            ->assertRedirect();

        // Verify the setting was persisted
        $stored = Setting::get('seo.robots_txt');
        $this->assertEquals($newContent, $stored);
    }

    // -------------------------------------------------------------------------
    // Sitemap
    // -------------------------------------------------------------------------

    public function test_sitemap_generation_route_exists(): void
    {
        // The sitemap.xml public route exists and returns a response
        $response = $this->get(route('sitemap'));

        // 200 OK (generated) or any non-404 response is acceptable
        $this->assertNotEquals(404, $response->getStatusCode());
    }

    public function test_sitemap_admin_page_requires_auth(): void
    {
        $this->get(route('setting.seo.sitemap.index'))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // Middleware redirect behaviour
    // -------------------------------------------------------------------------

    public function test_active_redirect_redirects_request(): void
    {
        SeoRedirect::create([
            'source_path' => '/old-page',
            'target_path' => '/new-page',
            'status_code' => 301,
            'is_active' => true,
        ]);

        $response = $this->get('/old-page');
        $response->assertRedirect('/new-page');
        $response->assertStatus(301);
    }

    public function test_inactive_redirect_does_not_redirect(): void
    {
        SeoRedirect::create([
            'source_path' => '/inactive-page',
            'target_path' => '/new-page',
            'status_code' => 301,
            'is_active' => false,
        ]);

        $response = $this->get('/inactive-page');
        $response->assertStatus(404);
    }

    public function test_redirect_increments_hits(): void
    {
        $redirect = SeoRedirect::create([
            'source_path' => '/hit-test',
            'target_path' => '/destination',
            'status_code' => 302,
            'is_active' => true,
            'hits_count' => 0,
        ]);

        $this->get('/hit-test');

        $redirect->refresh();
        $this->assertEquals(1, $redirect->hits_count);
    }

    public function test_redirect_normalizes_path_on_save(): void
    {
        $redirect = SeoRedirect::create([
            'source_path' => 'no-leading-slash',
            'target_path' => '/destination',
            'status_code' => 301,
            'is_active' => true,
        ]);

        $this->assertStringStartsWith('/', $redirect->source_path);
    }

    public function test_seo_redirect_model_is_permanent(): void
    {
        $redirect = new SeoRedirect(['status_code' => 301]);
        $this->assertTrue($redirect->isPermanent());
        $this->assertFalse($redirect->isTemporary());
    }

    public function test_seo_redirect_model_is_temporary(): void
    {
        $redirect = new SeoRedirect(['status_code' => 302]);
        $this->assertTrue($redirect->isTemporary());
        $this->assertFalse($redirect->isPermanent());
    }
}
