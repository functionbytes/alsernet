<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\HelpdeskLivechat\Database\Factories\WebFactory;
use Modules\HelpdeskLivechat\Models\WidgetSession;
use Tests\TestCase;

/**
 * Regression tests for HeartbeatRequest::authorize() — website_token validation.
 *
 * The authorize() method must reject requests that supply no X-Website-Token
 * header or an unknown token. A valid token must allow the request through
 * and result in a WidgetSession being created or updated.
 */
class HeartbeatWebsiteTokenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
    }

    // -----------------------------------------------------------------------
    // Failure paths
    // -----------------------------------------------------------------------

    public function test_missing_website_token_header_returns_403(): void
    {
        $this->postJson(route('helpdesk-livechat.widget.session.heartbeat'), [
            'session_token' => 'sess_abc123',
            'url' => 'https://example.com/page',
        ])->assertForbidden();
    }

    public function test_unknown_website_token_returns_403(): void
    {
        $this->withHeaders(['X-Website-Token' => 'totally-unknown-token'])
            ->postJson(route('helpdesk-livechat.widget.session.heartbeat'), [
                'session_token' => 'sess_abc123',
                'url' => 'https://example.com/page',
            ])->assertForbidden();
    }

    public function test_unknown_website_token_does_not_create_session(): void
    {
        $this->withHeaders(['X-Website-Token' => 'bad-token'])
            ->postJson(route('helpdesk-livechat.widget.session.heartbeat'), [
                'session_token' => 'sess_should_not_exist',
                'url' => 'https://example.com/page',
            ]);

        $this->assertDatabaseMissing('helpdesk_widget_sessions', [
            'session_token' => 'sess_should_not_exist',
        ]);
    }

    // -----------------------------------------------------------------------
    // Happy path
    // -----------------------------------------------------------------------

    public function test_valid_website_token_returns_200_and_creates_session(): void
    {
        $web = WebFactory::new()->create();
        $sessionToken = 'sess_'.uniqid();

        $this->withHeaders(['X-Website-Token' => $web->website_token])
            ->postJson(route('helpdesk-livechat.widget.session.heartbeat'), [
                'session_token' => $sessionToken,
                'url' => 'https://example.com/product',
                'title' => 'Product Page',
            ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['session_id']);

        $this->assertDatabaseHas('helpdesk_widget_sessions', [
            'session_token' => $sessionToken,
        ]);
    }

    public function test_valid_website_token_with_missing_required_fields_returns_422(): void
    {
        $web = WebFactory::new()->create();

        // session_token and url are required
        $this->withHeaders(['X-Website-Token' => $web->website_token])
            ->postJson(route('helpdesk-livechat.widget.session.heartbeat'), [
                // no session_token, no url
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['session_token', 'url']);
    }

    public function test_heartbeat_reuses_existing_session(): void
    {
        $web = WebFactory::new()->create();
        $sessionToken = 'sess_reuse_'.uniqid();

        // First heartbeat creates the session
        $this->withHeaders(['X-Website-Token' => $web->website_token])
            ->postJson(route('helpdesk-livechat.widget.session.heartbeat'), [
                'session_token' => $sessionToken,
                'url' => 'https://example.com/',
            ])->assertOk();

        $countBefore = WidgetSession::where('session_token', $sessionToken)->count();

        // Second heartbeat on same token should not duplicate the record
        $this->withHeaders(['X-Website-Token' => $web->website_token])
            ->postJson(route('helpdesk-livechat.widget.session.heartbeat'), [
                'session_token' => $sessionToken,
                'url' => 'https://example.com/',
            ])->assertOk();

        $this->assertSame($countBefore, WidgetSession::where('session_token', $sessionToken)->count());
    }
}
