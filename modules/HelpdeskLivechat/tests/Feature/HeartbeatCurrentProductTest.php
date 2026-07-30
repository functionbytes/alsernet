<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskLivechat\Database\Factories\WebFactory;
use Modules\HelpdeskLivechat\Models\WidgetSession;
use Modules\HelpdeskLivechat\Tests\Concerns\SeedsOpenConversationStatus;
use Tests\TestCase;

/**
 * El heartbeat persiste el producto que el visitante está viendo, para que el
 * agente lo vea antes de abrir el chat (covisualización estilo Oct8ne).
 */
class HeartbeatCurrentProductTest extends TestCase
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

    public function test_heartbeat_persists_current_product(): void
    {
        $web = WebFactory::new()->create();
        $token = 'sess_prod_'.uniqid();

        $this->withHeaders(['X-Website-Token' => $web->website_token])
            ->postJson(route('helpdesk-livechat.widget.session.heartbeat'), [
                'session_token' => $token,
                'url' => 'https://shop.example/product/42',
                'title' => 'Camiseta',
                'product' => [
                    'id' => '42',
                    'title' => 'Camiseta azul',
                    'image_url' => 'https://shop.example/img/42.jpg',
                    'price' => 19.9,
                    'currency' => 'EUR',
                ],
            ])->assertOk();

        $session = WidgetSession::on('helpdesk')->where('session_token', $token)->first();

        $this->assertNotNull($session);
        $this->assertSame('42', $session->current_product['id']);
        $this->assertSame('Camiseta azul', $session->current_product['title']);
        $this->assertSame(19.9, $session->current_product['price']);
    }

    public function test_heartbeat_without_product_leaves_current_product_null(): void
    {
        $web = WebFactory::new()->create();
        $token = 'sess_noprod_'.uniqid();

        $this->withHeaders(['X-Website-Token' => $web->website_token])
            ->postJson(route('helpdesk-livechat.widget.session.heartbeat'), [
                'session_token' => $token,
                'url' => 'https://shop.example/',
            ])->assertOk();

        $session = WidgetSession::on('helpdesk')->where('session_token', $token)->first();

        $this->assertNotNull($session);
        $this->assertNull($session->current_product);
    }

    public function test_invalid_product_without_id_is_rejected(): void
    {
        $web = WebFactory::new()->create();

        $this->withHeaders(['X-Website-Token' => $web->website_token])
            ->postJson(route('helpdesk-livechat.widget.session.heartbeat'), [
                'session_token' => 'sess_badprod_'.uniqid(),
                'url' => 'https://shop.example/product/1',
                'product' => ['title' => 'Sin id'],
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['product.id']);
    }
}
