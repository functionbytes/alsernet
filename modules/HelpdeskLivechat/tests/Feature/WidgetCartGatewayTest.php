<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Mockery;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskLivechat\Database\Factories\WebFactory;
use Modules\HelpdeskLivechat\Events\WidgetCartChanged;
use Modules\HelpdeskLivechat\Models\WidgetSession;
use Modules\HelpdeskLivechat\Services\Commerce\WidgetCartGateway;
use Modules\HelpdeskLivechat\Tests\Concerns\SeedsOpenConversationStatus;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;
use Tests\TestCase;

/**
 * Fase 3 live commerce: el agente/bot añade a la cesta del visitante desde el
 * servidor. Invitado solo con el token de la tienda; cliente solo verificado.
 */
class WidgetCartGatewayTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsOpenConversationStatus;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedOpenConversationStatus();
        Event::fake([WidgetCartChanged::class]);
    }

    private function conversation(array $cart, ?string $token, array $metadata = [], ?string $email = null): Conversation
    {
        $sessionToken = 'sess_gw_'.uniqid();
        WidgetSession::create([
            'session_token' => $sessionToken,
            'current_url' => 'https://shop.example/',
            'cart_snapshot' => $cart,
            'cart_id' => $cart['id'] ?? null,
            'cart_token' => $token,
            'started_at' => now(),
            'last_activity_at' => now(),
        ]);
        $customer = Customer::create(['email' => $email ?? 'guest-'.Str::random(8).'@anonymous.local', 'name' => 'Visitante']);

        return Conversation::factory()->create([
            'customer_id' => $customer->id,
            'channel' => 'web',
            'metadata' => $metadata + ['widget_session_token' => $sessionToken, 'widget_pubsub_token' => Str::random(32)],
        ]);
    }

    public function test_guest_cart_uses_the_store_token(): void
    {
        $bridge = Mockery::mock(PrestashopContextService::class);
        $bridge->shouldReceive('guestCartOperation')->once()
            ->with('add', 901, 'tok.sig', 43141, 2, null, Mockery::type('string'))
            ->andReturn(['cart_id' => 901, 'product_id' => 43141, 'quantity' => 2]);
        $bridge->shouldNotReceive('addCartProduct');

        $conversation = $this->conversation(['id' => 901, 'customer_logged' => false, 'lines' => []], 'tok.sig');
        $result = (new WidgetCartGateway($bridge))->addProduct($conversation, 43141, 0, 2);

        $this->assertTrue($result['ok']);
        Event::assertDispatched(WidgetCartChanged::class);
    }

    public function test_guest_without_token_is_refused(): void
    {
        $bridge = Mockery::mock(PrestashopContextService::class);
        $bridge->shouldNotReceive('guestCartOperation');

        $conversation = $this->conversation(['id' => 901, 'customer_logged' => false], null);

        $this->assertSame('no_cart_token', (new WidgetCartGateway($bridge))->addProduct($conversation, 43141)['error']);
        Event::assertNotDispatched(WidgetCartChanged::class);
    }

    public function test_logged_customer_requires_verified_identity(): void
    {
        $bridge = Mockery::mock(PrestashopContextService::class);
        $bridge->shouldNotReceive('addCartProduct');

        $conversation = $this->conversation(['id' => 902, 'customer_logged' => true], null, [], 'cliente@example.com');

        $this->assertSame('identity_not_verified', (new WidgetCartGateway($bridge))->addProduct($conversation, 43141)['error']);
    }

    public function test_verified_customer_goes_through_ownership_checked_action(): void
    {
        $bridge = Mockery::mock(PrestashopContextService::class);
        $bridge->shouldReceive('addCartProduct')->once()
            ->with(902, 43141, 1, null, 'cliente@example.com', null, Mockery::type('string'))
            ->andReturn(['cart_id' => 902, 'product_id' => 43141, 'quantity' => 1]);

        $conversation = $this->conversation(['id' => 902, 'customer_logged' => true], null, ['identity_verified' => true], 'cliente@example.com');

        $this->assertTrue((new WidgetCartGateway($bridge))->addProduct($conversation, 43141)['ok']);
    }

    public function test_no_cart_yet(): void
    {
        $bridge = Mockery::mock(PrestashopContextService::class);
        $conversation = $this->conversation([], null);

        $this->assertSame('no_cart', (new WidgetCartGateway($bridge))->addProduct($conversation, 43141)['error']);
    }

    public function test_cart_token_is_hidden_encrypted_and_kept_out_of_snapshot(): void
    {
        config(['helpdeskprestashop.webhook_secret' => 'test-secret-'.uniqid()]);
        $key = hash_hmac('sha256', 'alsernet-guest-cart-token:v1', (string) config('helpdeskprestashop.webhook_secret'));
        $body = rtrim(strtr(base64_encode(json_encode(['c' => 903, 'u' => 0, 'g' => 0, 'd' => 'x', 'e' => time() + 600])), '+/', '-_'), '=');
        $proof = $body.'.'.hash_hmac('sha256', $body, $key);
        $web = WebFactory::new()->create();
        $sessionToken = 'sess_tok_'.uniqid();

        $this->withHeaders(['X-Website-Token' => $web->website_token])
            ->postJson(route('helpdesk-livechat.widget.session.heartbeat'), [
                'session_token' => $sessionToken,
                'url' => 'https://shop.example/',
                'cart' => ['id' => 903, 'products_count' => 0, 'token' => $proof, 'lines' => []],
            ])->assertOk();

        $session = WidgetSession::on('helpdesk')->where('session_token', $sessionToken)->firstOrFail();

        $this->assertSame($proof, $session->cart_token);
        $this->assertSame(903, $session->cart_id);
        $this->assertArrayNotHasKey('token', $session->cart_snapshot);
        $this->assertArrayNotHasKey('cart_token', $session->toArray());
        $raw = DB::connection('helpdesk')->table('helpdesk_widget_sessions')->where('id', $session->id)->value('cart_token');
        $this->assertNotSame($proof, $raw);
    }
}
