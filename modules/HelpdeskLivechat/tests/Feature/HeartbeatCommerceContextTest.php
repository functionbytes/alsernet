<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use Modules\HelpdeskLivechat\Database\Factories\WebFactory;
use Modules\HelpdeskLivechat\Events\WidgetSessionUpdated;
use Modules\HelpdeskLivechat\Models\WidgetSession;
use Modules\HelpdeskLivechat\Tests\Concerns\SeedsOpenConversationStatus;
use Tests\TestCase;

/**
 * Live commerce (fase 1): el latido trae la cesta en vivo y los productos
 * vistos; se guardan en la sesión y se emiten al agente aunque la URL no cambie.
 */
class HeartbeatCommerceContextTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsOpenConversationStatus;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedOpenConversationStatus();
        Cache::flush();
    }

    private function cart(int $qty = 1): array
    {
        return [
            'id' => 901,
            'products_count' => $qty,
            'total' => 49.99 * $qty,
            'currency' => 'EUR',
            'customer_logged' => false,
            'lines' => [[
                'id_product' => 43141,
                'id_product_attribute' => 0,
                'name' => 'Estuche de limpieza',
                'qty' => $qty,
                'price' => 49.99,
                'total' => 49.99 * $qty,
                'image_url' => 'https://shop.example/img/43141.jpg',
                'url' => 'https://shop.example/43141-estuche',
            ]],
        ];
    }

    private function beat(string $websiteToken, array $payload): TestResponse
    {
        return $this->postJson(route('helpdesk-livechat.widget.session.heartbeat'), $payload + [
            'website_token' => $websiteToken,
            'url' => 'https://shop.example/',
        ]);
    }

    public function test_website_token_in_body_is_accepted_without_header(): void
    {
        $web = WebFactory::new()->create();

        $this->beat($web->website_token, ['session_token' => 'sess_body_'.uniqid()])->assertOk();
    }

    public function test_unknown_website_token_in_body_is_rejected(): void
    {
        $this->beat('nope-'.uniqid(), ['session_token' => 'sess_bad_'.uniqid()])->assertForbidden();
    }

    public function test_heartbeat_persists_cart_and_viewed_products(): void
    {
        $web = WebFactory::new()->create();
        $token = 'sess_cart_'.uniqid();

        $this->beat($web->website_token, [
            'session_token' => $token,
            'cart' => $this->cart(),
            'viewed_products' => [['id' => '43141', 'title' => 'Estuche', 'price' => 49.99]],
        ])->assertOk();

        $session = WidgetSession::on('helpdesk')->where('session_token', $token)->firstOrFail();

        $this->assertSame(901, $session->cart_snapshot['id']);
        $this->assertSame(43141, $session->cart_snapshot['lines'][0]['id_product']);
        $this->assertNotNull($session->cart_updated_at);
        $this->assertSame('43141', $session->viewed_products[0]['id']);
    }

    public function test_cart_change_on_same_url_skips_fast_path_and_broadcasts(): void
    {
        Event::fake([WidgetSessionUpdated::class]);
        $web = WebFactory::new()->create();
        $token = 'sess_cart_change_'.uniqid();
        Cache::put('helpdesklivechat:session_conv:'.$token, 1234, 60);

        $this->beat($web->website_token, ['session_token' => $token, 'cart' => $this->cart(1)])->assertOk();
        $this->beat($web->website_token, ['session_token' => $token, 'cart' => $this->cart(3)])->assertOk();

        $session = WidgetSession::on('helpdesk')->where('session_token', $token)->firstOrFail();
        $this->assertSame(3, $session->cart_snapshot['lines'][0]['qty']);
        Event::assertDispatchedTimes(WidgetSessionUpdated::class, 2);
    }

    public function test_absent_cart_keeps_stored_cart_and_null_cart_clears_it(): void
    {
        $web = WebFactory::new()->create();
        $token = 'sess_cart_keep_'.uniqid();

        $this->beat($web->website_token, ['session_token' => $token, 'cart' => $this->cart()])->assertOk();
        Cache::flush();
        $this->beat($web->website_token, ['session_token' => $token, 'url' => 'https://shop.example/otra'])->assertOk();

        $session = WidgetSession::on('helpdesk')->where('session_token', $token)->firstOrFail();
        $this->assertSame(901, $session->cart_snapshot['id']);

        Cache::flush();
        $this->beat($web->website_token, ['session_token' => $token, 'cart' => null])->assertOk();
        $this->assertNull($session->fresh()->cart_snapshot);
    }

    public function test_non_http_urls_are_dropped(): void
    {
        $web = WebFactory::new()->create();
        $token = 'sess_xss_'.uniqid();
        $cart = $this->cart();
        $cart['lines'][0]['image_url'] = 'javascript:alert(1)';
        $cart['lines'][0]['url'] = 'data:text/html,hi';

        $this->beat($web->website_token, [
            'session_token' => $token,
            'cart' => $cart,
            'viewed_products' => [['id' => '1', 'url' => 'javascript:alert(1)']],
        ])->assertOk();

        $session = WidgetSession::on('helpdesk')->where('session_token', $token)->firstOrFail();
        $this->assertNull($session->cart_snapshot['lines'][0]['image_url']);
        $this->assertNull($session->cart_snapshot['lines'][0]['url']);
        $this->assertNull($session->viewed_products[0]['url']);
    }

    public function test_cart_with_too_many_lines_is_rejected(): void
    {
        $web = WebFactory::new()->create();
        $cart = $this->cart();
        $cart['lines'] = array_fill(0, 51, $cart['lines'][0]);

        $this->beat($web->website_token, ['session_token' => 'sess_big_'.uniqid(), 'cart' => $cart])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cart.lines']);
    }
}
