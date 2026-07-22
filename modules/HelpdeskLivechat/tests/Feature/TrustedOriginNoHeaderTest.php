<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskLivechat\Database\Factories\WebFactory;
use Tests\TestCase;

/**
 * Regression tests for ValidateTrustedOrigin — "Origin required" case.
 *
 * When trusted domains ARE configured and a request arrives without an Origin
 * or Referer header, the middleware must reject it with 403 "Origin required".
 * This is the new strict-mode behavior added to prevent sandboxed/server-side
 * requests from being treated as trusted by omission.
 *
 * The "invalid Origin → Origin not allowed" case is already covered by
 * WidgetSecurityTest::test_invalid_origin_is_blocked_when_trusted_domains_set,
 * so it is not duplicated here.
 */
class TrustedOriginNoHeaderTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
    }

    // -----------------------------------------------------------------------
    // Failure path: trusted domains configured, no Origin header
    // -----------------------------------------------------------------------

    public function test_no_origin_header_with_global_trusted_domains_configured_returns_403(): void
    {
        Setting::set('livechat.trusted_domains', 'allowed.com', 'livechat');

        $web = WebFactory::new()->create(['trusted_domains' => null]);

        // Send request without Origin or Referer headers
        $response = $this->postJson(
            route('helpdesk-livechat.widget.conversation.store'),
            ['website_token' => $web->website_token, 'message' => 'test']
        );

        $response->assertForbidden()
            ->assertJsonPath('error', 'Origin required');
    }

    public function test_no_origin_header_with_per_inbox_trusted_domains_configured_returns_403(): void
    {
        // No global setting — per-inbox domains only
        Setting::where('key', 'livechat.trusted_domains')->delete();

        $web = WebFactory::new()->withTrustedDomains(['myshop.com'])->create();

        $response = $this->postJson(
            route('helpdesk-livechat.widget.conversation.store'),
            ['website_token' => $web->website_token, 'message' => 'test']
        );

        $response->assertForbidden()
            ->assertJsonPath('error', 'Origin required');
    }

    // -----------------------------------------------------------------------
    // Happy path: trusted domains configured, Origin header present and allowed
    // -----------------------------------------------------------------------

    public function test_with_allowed_origin_header_request_passes_middleware(): void
    {
        Setting::where('key', 'livechat.trusted_domains')->delete();

        $web = WebFactory::new()->withTrustedDomains(['myshop.com'])->create();

        $response = $this->withHeaders(['Origin' => 'https://myshop.com'])
            ->postJson(
                route('helpdesk-livechat.widget.conversation.store'),
                ['website_token' => $web->website_token, 'message' => 'hello']
            );

        // 403 from middleware would be "Origin not allowed" — anything else means middleware passed.
        $this->assertNotEquals(403, $response->status());
    }

    // -----------------------------------------------------------------------
    // Permissive mode: no domains configured → no Origin required
    // -----------------------------------------------------------------------

    public function test_no_origin_header_without_any_trusted_domains_is_allowed(): void
    {
        Setting::where('key', 'livechat.trusted_domains')->delete();

        $web = WebFactory::new()->create(['trusted_domains' => null]);

        $response = $this->postJson(
            route('helpdesk-livechat.widget.conversation.store'),
            ['website_token' => $web->website_token, 'message' => 'permissive']
        );

        // Must not be blocked with "Origin required". Se asserta siempre (no solo
        // en el caso 403) para que el test nunca quede "risky" sin aserciones:
        // si el status no es 403, json('error') no será 'Origin required'.
        $this->assertNotEquals(
            [403, 'Origin required'],
            [$response->status(), $response->json('error')],
            'Request without Origin must not be blocked when no trusted domains are configured'
        );
    }

    // -----------------------------------------------------------------------
    // Referer header as fallback
    // -----------------------------------------------------------------------

    public function test_referer_header_accepted_as_origin_fallback(): void
    {
        Setting::where('key', 'livechat.trusted_domains')->delete();

        $web = WebFactory::new()->withTrustedDomains(['trusted-shop.com'])->create();

        // No Origin, but Referer present with a trusted host
        $response = $this->withHeaders(['Referer' => 'https://trusted-shop.com/page'])
            ->postJson(
                route('helpdesk-livechat.widget.conversation.store'),
                ['website_token' => $web->website_token, 'message' => 'via referer']
            );

        $this->assertNotEquals(403, $response->status());
    }
}
