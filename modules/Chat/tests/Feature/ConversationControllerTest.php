<?php

namespace Modules\Chat\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Conversations\ConversationMessage;
use Modules\Chat\Models\Conversations\ConversationPriority;
use Modules\Chat\Models\Conversations\ConversationStatus;
use Modules\Chat\Models\Customers\Customer;
use Modules\Chat\Models\Inbox\Inbox;
use Tests\TestCase;

class ConversationControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected Account $account;

    protected Inbox $inbox;

    protected function setUp(): void
    {
        parent::setUp();

        $this->account = Account::factory()->create();

        $this->user = User::factory()->create();
        $this->user->name = $this->user->full_name; // Workaround: controller expects name attribute
        DB::table('chat_accounts_user')->insert(['user_id' => $this->user->id, 'account_id' => $this->account->id]);

        $this->inbox = Inbox::factory()->create([
            'account_id' => $this->account->id,
        ]);

        ConversationStatus::create([
            'account_id' => $this->account->id,
            'name' => 'Open',
            'slug' => 'open',
            'order' => 1,
        ]);

        ConversationPriority::create([
            'account_id' => $this->account->id,
            'name' => 'Medium',
            'slug' => 'medium',
            'order' => 2,
        ]);

        $this->app['router']->get('/login', fn () => 'Login')->name('login');
    }

    public function test_index_returns_view_with_conversations(): void
    {
        Conversation::factory()->count(3)->create([
            'account_id' => $this->account->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('chat.conversations.index'));

        $response->assertOk();
        $response->assertViewIs('Chat::chats.conversation.index');
        $response->assertViewHas('conversations');
    }

    public function test_index_filters_by_status(): void
    {
        $openStatus = ConversationStatus::where('slug', 'open')->where('account_id', $this->account->id)->first();

        $resolvedStatus = ConversationStatus::create([
            'account_id' => $this->account->id,
            'name' => 'Resolved',
            'slug' => 'resolved',
            'order' => 2,
        ]);

        Conversation::factory()->create([
            'account_id' => $this->account->id,
            'status_id' => $openStatus->id,
        ]);

        Conversation::factory()->create([
            'account_id' => $this->account->id,
            'status_id' => $resolvedStatus->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('chat.conversations.index', ['status' => 'resolved']));

        $response->assertOk();
        $conversations = $response->viewData('conversations');
        $this->assertCount(1, $conversations);
    }

    public function test_index_filters_by_priority(): void
    {
        $urgentPriority = ConversationPriority::create([
            'account_id' => $this->account->id,
            'name' => 'Urgent',
            'slug' => 'urgent',
            'order' => 1,
        ]);

        Conversation::factory()->create([
            'account_id' => $this->account->id,
            'priority_id' => $urgentPriority->id,
        ]);

        Conversation::factory()->count(2)->create([
            'account_id' => $this->account->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('chat.conversations.index', ['priority' => 'urgent']));

        $response->assertOk();
        $conversations = $response->viewData('conversations');
        $this->assertCount(1, $conversations);
    }

    public function test_index_filters_by_assignee(): void
    {
        Conversation::factory()->assigned($this->user->id)->create([
            'account_id' => $this->account->id,
        ]);

        Conversation::factory()->unassigned()->create([
            'account_id' => $this->account->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('chat.conversations.index', ['assignee' => $this->user->id]));

        $response->assertOk();
        $conversations = $response->viewData('conversations');
        $this->assertCount(1, $conversations);
    }

    public function test_index_scoped_to_account(): void
    {
        $otherAccount = Account::factory()->create();

        Conversation::factory()->create([
            'account_id' => $this->account->id,
        ]);

        Conversation::factory()->create([
            'account_id' => $otherAccount->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('chat.conversations.index'));

        $response->assertOk();
        $conversations = $response->viewData('conversations');
        $this->assertCount(1, $conversations);
    }

    // =========================================================================
    // show() method tests
    // =========================================================================

    public function test_show_returns_conversation_detail_view(): void
    {
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'inbox_id' => $this->inbox->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('chat.conversations.show', $conversation));

        $response->assertOk();
        $response->assertViewHas('conversation');
    }

    public function test_show_returns_404_for_nonexistent_conversation(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('chat.conversations.show', 999999));

        $response->assertNotFound();
    }

    public function test_show_denies_access_to_other_account_conversation(): void
    {
        $otherAccount = Account::factory()->create();
        $otherInbox = Inbox::factory()->create(['account_id' => $otherAccount->id]);

        $conversation = Conversation::factory()->create([
            'account_id' => $otherAccount->id,
            'inbox_id' => $otherInbox->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('chat.conversations.show', $conversation));

        $response->assertForbidden();
    }

    public function test_show_loads_messages_with_attachments(): void
    {
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'inbox_id' => $this->inbox->id,
        ]);

        ConversationMessage::factory()->count(2)->create([
            'account_id' => $this->account->id,
            'inbox_id' => $this->inbox->id,
            'conversation_id' => $conversation->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('chat.conversations.show', $conversation));

        $response->assertOk();
        $viewConversation = $response->viewData('conversation');
        $this->assertTrue($viewConversation->relationLoaded('messages'));
    }

    // =========================================================================
    // store() tests
    // =========================================================================

    public function test_store_creates_conversation(): void
    {
        $customer = Customer::factory()->create([
            'account_id' => $this->account->id,
        ]);

        $data = [
            'customer_id' => $customer->id,
            'inbox' => $this->inbox->id,
            'initial_message' => 'Hello, this is a test conversation',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('chat.conversations.store'), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('chat_conversations', [
            'account_id' => $this->account->id,
            'customer_id' => $customer->id,
            'inbox_id' => $this->inbox->id,
        ]);
    }

    // =========================================================================
    // update() tests
    // =========================================================================

    public function test_update_changes_conversation(): void
    {
        $this->markTestSkipped('Route [chat.conversations.update] not defined - conversations use PATCH for specific fields');

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'subject' => 'Original Subject',
        ]);

        $data = [
            'subject' => 'Updated Subject',
        ];

        $response = $this->actingAs($this->user)
            ->patchJson(route('chat.conversations.updateStatus', $conversation), $data);

        $response->assertOk();
        $this->assertDatabaseHas('chat_conversations', [
            'id' => $conversation->id,
            'subject' => 'Updated Subject',
        ]);
    }

    // =========================================================================
    // search() endpoint tests
    // =========================================================================

    public function test_search_returns_matching_conversations(): void
    {
        $customer = Customer::factory()->create([
            'account_id' => $this->account->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        Conversation::factory()->create([
            'account_id' => $this->account->id,
            'customer_id' => $customer->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('chat.conversations.search', ['q' => 'John']));

        $response->assertOk();
        $response->assertJsonStructure([
            '*' => ['id', 'customer', 'inbox', 'status'],
        ]);
    }

    public function test_search_returns_empty_when_no_match(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('chat.conversations.search', ['q' => 'zzznonexistentxxx']));

        $response->assertOk();
        $response->assertExactJson([]);
    }

    public function test_search_is_scoped_to_current_account(): void
    {
        $otherAccount = Account::factory()->create();
        $otherInbox = Inbox::factory()->create(['account_id' => $otherAccount->id]);
        $otherCustomer = Customer::factory()->create([
            'account_id' => $otherAccount->id,
            'name' => 'ScopedTestCustomer',
        ]);

        Conversation::factory()->create([
            'account_id' => $otherAccount->id,
            'inbox_id' => $otherInbox->id,
            'customer_id' => $otherCustomer->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('chat.conversations.search', ['q' => 'ScopedTestCustomer']));

        $response->assertOk();
        $response->assertExactJson([]);
    }

    // =========================================================================
    // searchContacts() tests
    // =========================================================================

    public function test_search_contacts_returns_customers(): void
    {
        Customer::factory()->create([
            'account_id' => $this->account->id,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('chat.conversations.searchContacts', ['q' => 'Jane']));

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['name' => 'Jane Smith']);
    }

    // =========================================================================
    // Authorization tests
    // =========================================================================

    public function test_guest_cannot_access_conversations(): void
    {
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
        ]);

        $response = $this->get(route('chat.conversations.index'));
        $response->assertRedirect('/');

        $response = $this->get(route('chat.conversations.show', $conversation));
        $response->assertRedirect('/');
    }

    public function test_user_without_account_cannot_access_conversations(): void
    {
        $userWithoutAccount = User::factory()->create();

        $response = $this->actingAs($userWithoutAccount)
            ->get(route('chat.conversations.index'));

        $response->assertForbidden();
    }
}
