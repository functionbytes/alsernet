<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Modules\HelpdeskLivechat\Models\Channels\Web;
use Tests\TestCase;

class WidgetSpaTest extends TestCase
{
    public function test_spa_mountpoint_loads_without_token(): void
    {
        $response = $this->get('/hd/widget');

        $response->assertOk();
        $response->assertSee('id="widget-root"', false);
    }

    public function test_spa_mountpoint_passes_config_for_valid_token(): void
    {
        $web = Web::create([
            'website_url' => 'https://example.com',
            'widget_color' => '#90bb13',
            'widget_position' => 'right',
            'welcome_title' => 'Hi there!',
            'welcome_tagline' => 'How can we help?',
        ]);

        $response = $this->get('/hd/widget?website_token='.$web->website_token);

        $response->assertOk();
        $response->assertSee('HELPDESK_WIDGET_CONFIG', false);
    }

    public function test_settings_endpoint_requires_token(): void
    {
        $response = $this->get('/hd/api/settings');

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    public function test_settings_endpoint_returns_404_for_invalid_token(): void
    {
        $response = $this->get('/hd/api/settings?website_token=invalid_token_that_does_not_exist');

        $response->assertNotFound();
    }

    public function test_settings_endpoint_returns_config_for_valid_token(): void
    {
        $web = Web::create([
            'website_url' => 'https://example.com',
            'widget_color' => '#FF0000',
            'widget_position' => 'left',
        ]);

        $response = $this->get('/hd/api/settings?website_token='.$web->website_token);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [
                'theme' => [
                    'primary_color' => '#FF0000',
                    'position' => 'left',
                ],
            ],
        ]);
    }
}
