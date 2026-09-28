<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Modules\Helpdesk\Events\ConversationCreated;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskLivechat\Database\Factories\WebFactory;
use Modules\HelpdeskLivechat\Models\WidgetSession;
use Modules\HelpdeskLivechat\Tests\Concerns\SeedsOpenConversationStatus;
use Tests\TestCase;

/**
 * La tienda firma el email del cliente logueado (identifier + identifier_hash,
 * HMAC-SHA256 con el hmac_token del canal). Solo con firma válida el chat se
 * asocia a ese cliente por encima de lo que diga la sesión del widget.
 */
class WidgetVerifiedIdentityTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsOpenConversationStatus;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake([ConversationCreated::class]);
        $this->seedOpenConversationStatus();
    }

    private function create(array $payload)
    {
        return $this->postJson(route('helpdesk-livechat.widget.conversation.store'), $payload + ['message' => 'Hola']);
    }

    public function test_valid_signature_marks_conversation_as_verified_customer(): void
    {
        $web = WebFactory::new()->create();
        $email = 'cliente.'.uniqid().'@example.com';

        $response = $this->create([
            'website_token' => $web->website_token,
            'email' => $email,
            'identifier' => $email,
            'identifier_hash' => hash_hmac('sha256', $email, $web->hmac_token),
        ])->assertOk();

        $conversation = Conversation::findOrFail($response->json('data.conversation_id'));
        $this->assertSame($email, $conversation->customer->email);
        $this->assertTrue($conversation->metadata['identity_verified'] ?? false);
    }

    public function test_invalid_signature_is_not_verified(): void
    {
        $web = WebFactory::new()->create();
        $email = 'cliente.'.uniqid().'@example.com';

        $response = $this->create([
            'website_token' => $web->website_token,
            'email' => $email,
            'identifier' => $email,
            'identifier_hash' => hash_hmac('sha256', $email, 'otro-secreto'),
        ])->assertOk();

        $conversation = Conversation::findOrFail($response->json('data.conversation_id'));
        $this->assertArrayNotHasKey('identity_verified', $conversation->metadata ?? []);
    }

    public function test_verified_identity_wins_over_guest_customer_linked_to_session(): void
    {
        $web = WebFactory::new()->create();
        $guest = Customer::create(['email' => 'guest-'.uniqid().'@anonymous.local', 'name' => 'Guest']);
        $sessionToken = 'sess_verified_'.uniqid();
        WidgetSession::create([
            'session_token' => $sessionToken,
            'current_url' => 'https://shop.example/',
            'customer_id' => $guest->id,
            'started_at' => now(),
            'last_activity_at' => now(),
        ]);
        $email = 'logueado.'.uniqid().'@example.com';

        $response = $this->create([
            'website_token' => $web->website_token,
            'widget_session_token' => $sessionToken,
            'email' => $email,
            'identifier' => $email,
            'identifier_hash' => hash_hmac('sha256', $email, $web->hmac_token),
        ])->assertOk();

        $this->assertSame($email, $response->json('data.customer.email'));
        $this->assertSame((int) $response->json('data.customer_id'), (int) WidgetSession::where('session_token', $sessionToken)->value('customer_id'));
        $this->assertSame('Guest', $guest->fresh()->name);
    }

    public function test_unsigned_email_does_not_override_session_customer(): void
    {
        $web = WebFactory::new()->create();
        $known = Customer::create(['email' => 'conocido.'.uniqid().'@example.com', 'name' => 'Conocido']);
        $sessionToken = 'sess_unsigned_'.uniqid();
        WidgetSession::create([
            'session_token' => $sessionToken,
            'current_url' => 'https://shop.example/',
            'customer_id' => $known->id,
            'started_at' => now(),
            'last_activity_at' => now(),
        ]);

        $response = $this->create([
            'website_token' => $web->website_token,
            'widget_session_token' => $sessionToken,
            'email' => 'otro.'.uniqid().'@example.com',
        ])->assertOk();

        $this->assertSame($known->id, (int) $response->json('data.customer_id'));
    }
}
