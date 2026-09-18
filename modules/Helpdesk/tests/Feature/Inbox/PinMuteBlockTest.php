<?php

namespace Modules\Helpdesk\Tests\Feature\Inbox;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\Customer;

class PinMuteBlockTest extends InboxTestCase
{
    public function test_toggle_pin_creates_pin_record(): void
    {
        $conversation = $this->createConversation();

        $response = $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.pin', $conversation));

        $response->assertOk()
            ->assertJson(['success' => true, 'pinned' => true]);

        $meta = DB::connection('helpdesk')
            ->table('helpdesk_user_conversation_meta')
            ->where('user_id', $this->manager->id)
            ->where('conversation_id', $conversation->id)
            ->first();

        $this->assertNotNull($meta);
        $this->assertNotNull($meta->pinned_at);
    }

    public function test_toggle_pin_twice_removes_pin(): void
    {
        $conversation = $this->createConversation();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.pin', $conversation));

        $response = $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.pin', $conversation));

        $response->assertOk()
            ->assertJson(['success' => true, 'pinned' => false]);

        $meta = DB::connection('helpdesk')
            ->table('helpdesk_user_conversation_meta')
            ->where('user_id', $this->manager->id)
            ->where('conversation_id', $conversation->id)
            ->first();

        $this->assertNull($meta->pinned_at);
    }

    public function test_toggle_mute_sets_muted_until(): void
    {
        $conversation = $this->createConversation();
        $until = now()->addDays(3)->toDateTimeString();

        $response = $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.mute', $conversation), [
                'until' => $until,
            ]);

        $response->assertOk()
            ->assertJson(['success' => true, 'muted' => true]);

        $meta = DB::connection('helpdesk')
            ->table('helpdesk_user_conversation_meta')
            ->where('user_id', $this->manager->id)
            ->where('conversation_id', $conversation->id)
            ->first();

        $this->assertNotNull($meta);
        $this->assertNotNull($meta->muted_until);
    }

    public function test_toggle_mute_defaults_to_7_days_when_no_until(): void
    {
        $conversation = $this->createConversation();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.mute', $conversation))
            ->assertOk()
            ->assertJson(['muted' => true]);

        $meta = DB::connection('helpdesk')
            ->table('helpdesk_user_conversation_meta')
            ->where('user_id', $this->manager->id)
            ->where('conversation_id', $conversation->id)
            ->first();

        $this->assertNotNull($meta->muted_until);
    }

    public function test_block_contact_bans_customer(): void
    {
        $customer = $this->createCustomer();
        $conversation = $this->createConversation(['customer_id' => $customer->id]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.block-contact', $conversation))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNotNull(
            Customer::find($customer->id)->banned_at
        );
    }

    public function test_block_contact_requires_delete_permission(): void
    {
        $customer = $this->createCustomer();
        $conversation = $this->createConversation(['customer_id' => $customer->id]);
        $user = User::factory()->create();
        $user->givePermissionTo([
            'helpdesk.conversations.view',
            'helpdesk.conversations.update',
        ]);

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.conversations.block-contact', $conversation))
            ->assertForbidden();
    }

    public function test_pin_requires_auth(): void
    {
        $conversation = $this->createConversation();

        $this->post(route('manager.helpdesk.conversations.pin', $conversation))
            ->assertRedirect();
    }
}
