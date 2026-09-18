<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Helpdesk\Events\ConversationCreated;
use Modules\HelpdeskLivechat\Database\Factories\WebFactory;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Modules\HelpdeskLivechat\Tests\Concerns\SeedsOpenConversationStatus;
use Tests\TestCase;

/**
 * Per-website_token quota on the public widget endpoints (S1 hardening).
 * ValidateTrustedOrigin is not an authentication control (Origin/Referer are
 * spoofable), so the aggregate volume per store token must be capped
 * independently of the per-IP throttle.
 */
class WebsiteTokenRateLimitTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsOpenConversationStatus;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private Web $web;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([ConversationCreated::class]);

        $this->seedOpenConversationStatus();

        $this->web = WebFactory::new()->create();

        // Shrink the token quota so the test doesn't need hundreds of requests,
        // and lift the per-IP throttle out of the way (its limit is lower).
        config([
            'helpdesklivechat.token_rate_limits.conversations' => [
                'max_attempts' => 2,
                'decay_seconds' => 3600,
            ],
        ]);

        RateLimiter::clear('helpdesklivechat:token-throttle:conversations:'.sha1($this->web->website_token));
    }

    private function createConversation(string $token)
    {
        return $this->postJson(route('helpdesk-livechat.widget.conversation.store'), [
            'website_token' => $token,
            'email' => 'visitor@example.com',
            'message' => 'Hola',
        ]);
    }

    public function test_conversation_creation_is_limited_per_website_token(): void
    {
        $this->createConversation($this->web->website_token)->assertStatus(200);
        $this->createConversation($this->web->website_token)->assertStatus(200);

        $third = $this->createConversation($this->web->website_token);

        $third->assertStatus(429);
        $third->assertHeader('Retry-After');
    }

    public function test_other_tokens_are_not_affected_by_an_exhausted_token(): void
    {
        $other = WebFactory::new()->create();
        RateLimiter::clear('helpdesklivechat:token-throttle:conversations:'.sha1($other->website_token));

        // Exhaust the first token's quota.
        $this->createConversation($this->web->website_token);
        $this->createConversation($this->web->website_token);
        $this->createConversation($this->web->website_token)->assertStatus(429);

        // A different store token still works.
        $this->createConversation($other->website_token)->assertStatus(200);
    }

    public function test_zero_max_attempts_disables_the_bucket(): void
    {
        config([
            'helpdesklivechat.token_rate_limits.conversations' => [
                'max_attempts' => 0,
                'decay_seconds' => 3600,
            ],
        ]);

        $this->createConversation($this->web->website_token)->assertStatus(200);
        $this->createConversation($this->web->website_token)->assertStatus(200);
        $this->createConversation($this->web->website_token)->assertStatus(200);
    }
}
