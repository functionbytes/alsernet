<?php

namespace Modules\Helpdesk\Tests\Feature;

use App\Models\User;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

class SupervisorReviewTest extends HelpdeskTestCase
{
    public function test_agent_can_request_a_supervisor_review(): void
    {
        $conversation = $this->createConversation();

        $response = $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.supervisor-review.store', $conversation), [
                'review_type' => 'approve_discount',
                'comment' => 'Cliente pide un 20% de descuento, ¿lo aprobamos?',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('helpdesk_supervisor_reviews', [
            'id' => $response->json('review.id'),
            'conversation_id' => $conversation->id,
            'requested_by' => $this->manager->id,
            'review_type' => 'approve_discount',
            'comment' => 'Cliente pide un 20% de descuento, ¿lo aprobamos?',
            'status' => 'pending',
        ], 'helpdesk');
    }

    public function test_review_type_must_be_a_known_option(): void
    {
        $conversation = $this->createConversation();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.supervisor-review.store', $conversation), [
                'review_type' => 'not_a_real_type',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('review_type');
    }

    public function test_user_without_permission_cannot_request_a_review(): void
    {
        $conversation = $this->createConversation();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.conversations.supervisor-review.store', $conversation), [
                'review_type' => 'approve_response',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('helpdesk_supervisor_reviews', 0, 'helpdesk');
    }

    public function test_guest_cannot_request_a_review(): void
    {
        $conversation = $this->createConversation();

        $this->postJson(route('manager.helpdesk.conversations.supervisor-review.store', $conversation), [
            'review_type' => 'approve_response',
        ])->assertUnauthorized();
    }

    private function createConversation(): Conversation
    {
        $conversation = Conversation::factory()->create(['customer_id' => Customer::factory()->create()->id]);
        $conversation->status_id = $this->openStatus->id;
        $conversation->save();

        return $conversation;
    }
}
