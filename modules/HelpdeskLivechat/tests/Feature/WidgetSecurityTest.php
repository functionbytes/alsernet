<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskLivechat\Database\Factories\WebFactory;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Modules\HelpdeskLivechat\Tests\Concerns\SeedsOpenConversationStatus;
use Tests\Concerns\SeedsHelpdeskRoles;
use Tests\TestCase;

class WidgetSecurityTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsHelpdeskRoles;
    use SeedsOpenConversationStatus;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        // Los listeners de ConversationCreated consultan el rol `helpdesk-agent`;
        // la tabla roles del snapshot está vacía y sin esto el store devuelve 500.
        $this->seedHelpdeskRoles();

        $this->seedOpenConversationStatus();
    }

    public function test_invalid_origin_is_blocked_when_trusted_domains_set(): void
    {
        // The ValidateTrustedOrigin middleware reads from helpdesk_settings,
        // not from the Web model's trusted_domains column.
        Setting::set('livechat.trusted_domains', 'allowed.com', 'livechat');

        WebFactory::new()->create(['website_token' => 'test-token-blocked']);

        $response = $this->withHeaders(['Origin' => 'https://evil.com'])
            ->postJson(route('helpdesk-livechat.widget.conversation.store'), [
                'website_token' => 'test-token-blocked',
                'message' => 'Should be blocked',
            ]);

        $response->assertForbidden()
            ->assertJsonPath('error', 'Origin not allowed');
    }

    public function test_origin_allowed_when_in_trusted_domains(): void
    {
        Setting::set('livechat.trusted_domains', 'allowed.com', 'livechat');

        $web = WebFactory::new()->create();

        $response = $this->withHeaders(['Origin' => 'https://allowed.com'])
            ->postJson(route('helpdesk-livechat.widget.conversation.store'), [
                'website_token' => $web->website_token,
                'message' => 'Should be allowed',
            ]);

        // 200 (success) or 500 (backend error) is fine — as long as it's not 403
        $this->assertNotEquals(403, $response->status(), 'Request from trusted origin should not be blocked');
    }

    public function test_no_trusted_domains_allows_any_origin(): void
    {
        // Ensure no trusted domains are configured
        Setting::where('key', 'livechat.trusted_domains')->delete();

        $web = WebFactory::new()->create();

        $response = $this->withHeaders(['Origin' => 'https://random-site.com'])
            ->postJson(route('helpdesk-livechat.widget.conversation.store'), [
                'website_token' => $web->website_token,
                'message' => 'Permissive mode',
            ]);

        $this->assertNotEquals(403, $response->status(), 'No trusted domains configured — all origins should be allowed');
    }

    public function test_xss_in_message_body_is_sanitized(): void
    {
        $web = WebFactory::new()->create();

        // Create a customer + conversation first so we can send a message
        $create = $this->postJson(route('helpdesk-livechat.widget.conversation.store'), [
            'website_token' => $web->website_token,
            'email' => 'xss@example.com',
            'message' => 'Initial message',
        ]);

        $create->assertOk();
        $conversationId = $create->json('data.conversation_id');
        $customerId = $create->json('data.customer_id');
        $token = $create->json('data.pubsub_token');

        $xssPayload = "<script>alert('xss')</script>hola";

        $response = $this->withHeaders(['X-Conversation-Token' => $token])->postJson(
            route('helpdesk-livechat.widget.conversation.messages.send', $conversationId),
            [
                'content' => $xssPayload,
                'customer_id' => $customerId,
            ]
        );

        $response->assertOk();

        // Verify the stored body does not contain <script> tags.
        // Conexión 'helpdesk': las escrituras del widget van por esa conexión y,
        // con ambas transaccionadas, la conexión por defecto no las ve.
        $this->assertDatabaseMissing('helpdesk_conversation_items', [
            'conversation_id' => $conversationId,
            'body' => $xssPayload,
        ], 'helpdesk');

        $item = ConversationItem::where('conversation_id', $conversationId)
            ->where('type', 'message')
            ->latest()
            ->first();

        $this->assertNotNull($item);
        $this->assertStringNotContainsString('<script>', (string) $item->body);
    }

    public function test_invalid_mime_type_is_rejected(): void
    {
        Storage::fake('public');

        $web = WebFactory::new()->create();

        $create = $this->postJson(route('helpdesk-livechat.widget.conversation.store'), [
            'website_token' => $web->website_token,
            'email' => 'upload@example.com',
            'message' => 'Testing upload',
        ]);

        $conversationId = $create->json('data.conversation_id');
        $customerId = $create->json('data.customer_id');
        $token = $create->json('data.pubsub_token');

        $exeFile = UploadedFile::fake()->create('malware.exe', 100, 'application/octet-stream');

        $response = $this->withHeaders(['X-Conversation-Token' => $token])->postJson(
            route('helpdesk-livechat.widget.conversation.messages.send', $conversationId),
            [
                'customer_id' => $customerId,
                'attachments' => [$exeFile],
            ]
        );

        $response->assertUnprocessable();
    }
}
