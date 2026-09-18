<?php

namespace Modules\Helpdesk\Tests\Feature;

use App\Models\User;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

/**
 * AI reply suggestions endpoint used by the inbox "ai-suggest" modal
 * (modules/Helpdesk/resources/views/helpdesk/inbox/partials/modals/ai-suggest.blade.php).
 */
class AiSuggestionsControllerTest extends HelpdeskTestCase
{
    private const ROUTE = 'manager.helpdesk.conversations.ai.suggestions';

    public function test_manager_can_fetch_ai_suggestions(): void
    {
        $conversation = $this->createConversation();
        $customer = Customer::factory()->create();

        ConversationItem::factory()->fromCustomer($customer->id)->create([
            'conversation_id' => $conversation->id,
            'body' => 'Necesito ayuda con mi pedido',
        ]);

        $this->actingAs($this->manager)
            ->postJson(route(self::ROUTE, $conversation))
            ->assertOk()
            ->assertJsonStructure(['success', 'message', 'data' => ['suggestions', 'last_message']])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.last_message', 'Necesito ayuda con mi pedido');
    }

    public function test_ai_disabled_returns_empty_suggestions_without_error(): void
    {
        // AiClient has no API key configured in the test environment, so the
        // service must degrade gracefully to an empty list instead of a 500.
        $conversation = $this->createConversation();

        $this->actingAs($this->manager)
            ->postJson(route(self::ROUTE, $conversation))
            ->assertOk()
            ->assertJsonPath('data.suggestions', []);
    }

    public function test_guest_cannot_fetch_ai_suggestions(): void
    {
        $conversation = $this->createConversation();

        $this->post(route(self::ROUTE, $conversation))
            ->assertRedirect(route('auth.login'));
    }

    public function test_user_without_permission_cannot_fetch_ai_suggestions(): void
    {
        $conversation = $this->createConversation();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route(self::ROUTE, $conversation))
            ->assertForbidden();
    }

    private function createConversation(array $overrides = []): Conversation
    {
        $conversation = Conversation::factory()->create(array_merge(['channel' => 'web'], $overrides));
        $conversation->status_id = $this->openStatus->id;
        $conversation->save();

        return $conversation;
    }
}
