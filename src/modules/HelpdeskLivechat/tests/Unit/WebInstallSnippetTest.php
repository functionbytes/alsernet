<?php

namespace Modules\HelpdeskLivechat\Tests\Unit;

use Modules\HelpdeskLivechat\Models\Channels\Web;
use Tests\TestCase;

/**
 * Unit coverage for Web::getInstallSnippet() — verifies that each supported
 * cms_type produces a snippet containing platform-specific markers and the
 * widget's website_token. Does not hit the database; instantiates the model
 * directly with attributes set via fill().
 */
class WebInstallSnippetTest extends TestCase
{
    private function buildWeb(string $cms): Web
    {
        $web = new Web;
        $web->cms_type = $cms;
        $web->website_token = 'test_token_xyz';

        return $web;
    }

    public function test_custom_snippet_uses_generic_loader(): void
    {
        $snippet = $this->buildWeb('custom')->getInstallSnippet();

        $this->assertStringContainsString('test_token_xyz', $snippet);
        $this->assertStringContainsString('build-helpdesklivechat/widget.js', $snippet);
        $this->assertStringContainsString('Helpdesk Chat Widget', $snippet);
    }

    public function test_prestashop_snippet_includes_prestashop_globals(): void
    {
        $snippet = $this->buildWeb('prestashop')->getInstallSnippet();

        $this->assertStringContainsString('test_token_xyz', $snippet);
        $this->assertStringContainsString('PrestaShop', $snippet);
        $this->assertStringContainsString('prestashop.customer', $snippet);
        $this->assertStringContainsString("platform: 'prestashop'", $snippet);
    }

    public function test_shopify_snippet_includes_liquid_customer_block(): void
    {
        $snippet = $this->buildWeb('shopify')->getInstallSnippet();

        $this->assertStringContainsString('test_token_xyz', $snippet);
        $this->assertStringContainsString('Shopify', $snippet);
        $this->assertStringContainsString('{% if customer %}', $snippet);
        $this->assertStringContainsString("platform: 'shopify'", $snippet);
    }

    public function test_woocommerce_snippet_includes_wp_user_block(): void
    {
        $snippet = $this->buildWeb('woocommerce')->getInstallSnippet();

        $this->assertStringContainsString('test_token_xyz', $snippet);
        $this->assertStringContainsString('is_user_logged_in', $snippet);
        $this->assertStringContainsString("platform: 'woocommerce'", $snippet);
    }

    public function test_magento_and_wordpress_use_generic_snippet_with_note(): void
    {
        foreach (['magento', 'wordpress'] as $cms) {
            $snippet = $this->buildWeb($cms)->getInstallSnippet();

            $this->assertStringContainsString('test_token_xyz', $snippet);
            $this->assertStringContainsString('Próximamente', $snippet, "missing 'próximamente' note for {$cms}");
        }
    }

    public function test_unknown_cms_type_falls_back_to_generic_snippet(): void
    {
        $web = new Web;
        $web->cms_type = 'unknown_platform';
        $web->website_token = 'fallback_token';

        $snippet = $web->getInstallSnippet();

        $this->assertStringContainsString('fallback_token', $snippet);
        $this->assertStringContainsString('build-helpdesklivechat/widget.js', $snippet);
    }
}
