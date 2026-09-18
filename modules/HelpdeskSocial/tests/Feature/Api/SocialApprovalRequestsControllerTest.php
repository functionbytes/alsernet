<?php

namespace Modules\HelpdeskSocial\Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskSocial\Models\SocialApprovalRequest;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Tests\TestCase;

class SocialApprovalRequestsControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected User $approver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPermissions();

        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            'helpdesksocial.view',
            'helpdesksocial.manage',
        ]);

        $this->approver = User::factory()->create();
        $this->approver->givePermissionTo([
            'helpdesksocial.view',
            'helpdesksocial.approver',
        ]);
    }

    private function seedPermissions(): void {}

    public function test_index_requires_auth(): void
    {
        $response = $this->getJson('/api/helpdesk/social/approval-requests');

        $response->assertUnauthorized();
    }

    public function test_index_returns_data(): void
    {
        SocialApprovalRequest::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/helpdesk/social/approval-requests');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.per_page', 25);
    }

    public function test_index_forbidden_without_permission(): void
    {
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser, 'sanctum');

        $response = $this->getJson('/api/helpdesk/social/approval-requests');

        $response->assertForbidden();
    }

    public function test_store_creates_resource(): void
    {
        $comment = SocialComment::factory()->create();

        $payload = [
            'social_comment_id' => $comment->id,
            'action_type' => 'reply',
            'payload' => ['body' => 'Reply text'],
            'approver_user_id' => $this->approver->id,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/helpdesk/social/approval-requests', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.action_type', 'reply');

        $this->assertDatabaseHas('helpdesk_social_approval_requests', [
            'social_comment_id' => $comment->id,
            'action_type' => 'reply',
            'requested_by_user_id' => $this->user->id,
            'status' => 'pending',
        ]);
    }

    public function test_store_validation_fails(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/helpdesk/social/approval-requests', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['social_comment_id', 'action_type']);
    }

    public function test_store_forbidden_without_manage_permission(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo(['helpdesksocial.view']);

        $comment = SocialComment::factory()->create();

        $response = $this->actingAs($viewer, 'sanctum')
            ->postJson('/api/helpdesk/social/approval-requests', [
                'social_comment_id' => $comment->id,
                'action_type' => 'reply',
            ]);

        $response->assertForbidden();
    }

    public function test_show_returns_resource(): void
    {
        $approvalRequest = SocialApprovalRequest::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/helpdesk/social/approval-requests/{$approvalRequest->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $approvalRequest->id);
    }

    public function test_approve(): void
    {
        $approvalRequest = SocialApprovalRequest::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($this->approver, 'sanctum')
            ->postJson("/api/helpdesk/social/approval-requests/{$approvalRequest->id}/approve", [
                'approver_note' => 'Approved',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('helpdesk_social_approval_requests', [
            'id' => $approvalRequest->id,
            'status' => 'approved',
            'approver_note' => 'Approved',
        ]);
    }

    public function test_approve_fails_when_already_responded(): void
    {
        $approvalRequest = SocialApprovalRequest::factory()->create(['status' => 'approved']);

        $response = $this->actingAs($this->approver, 'sanctum')
            ->postJson("/api/helpdesk/social/approval-requests/{$approvalRequest->id}/approve");

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'La solicitud ya fue respondida.');
    }

    public function test_reject(): void
    {
        $approvalRequest = SocialApprovalRequest::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($this->approver, 'sanctum')
            ->postJson("/api/helpdesk/social/approval-requests/{$approvalRequest->id}/reject", [
                'approver_note' => 'Rejected',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'rejected');

        $this->assertDatabaseHas('helpdesk_social_approval_requests', [
            'id' => $approvalRequest->id,
            'status' => 'rejected',
            'approver_note' => 'Rejected',
        ]);
    }

    public function test_reject_fails_when_already_responded(): void
    {
        $approvalRequest = SocialApprovalRequest::factory()->create(['status' => 'rejected']);

        $response = $this->actingAs($this->approver, 'sanctum')
            ->postJson("/api/helpdesk/social/approval-requests/{$approvalRequest->id}/reject");

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'La solicitud ya fue respondida.');
    }

    public function test_approve_forbidden_without_approver_permission(): void
    {
        $approvalRequest = SocialApprovalRequest::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/helpdesk/social/approval-requests/{$approvalRequest->id}/approve");

        $response->assertForbidden();
    }
}
