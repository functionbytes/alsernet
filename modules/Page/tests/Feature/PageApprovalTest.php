<?php

namespace Modules\Page\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Page\Enums\PageStatus;
use Modules\Page\Models\Page;
use Modules\Page\Models\PageApproval;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PageApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'page.update', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'page.approve', 'guard_name' => 'web']);
    }

    private function userWithPermission(string ...$permissions): User
    {
        $user = User::factory()->create();
        foreach ($permissions as $perm) {
            $user->givePermissionTo($perm);
        }

        return $user;
    }

    // =========================================================================
    // Request approval
    // =========================================================================

    public function test_editor_can_request_approval_for_draft_page(): void
    {
        $editor = $this->userWithPermission('page.update');
        $page = Page::factory()->draft()->create();

        $this->actingAs($editor)
            ->post(route('pages.approval.request', $page), ['notes' => 'Ready for review.'])
            ->assertRedirect();

        $this->assertDatabaseHas('page_approvals', [
            'page_id' => $page->id,
            'requested_by' => $editor->id,
            'status' => 'pending',
            'notes' => 'Ready for review.',
        ]);
    }

    public function test_approval_creates_database_record(): void
    {
        $editor = $this->userWithPermission('page.update');
        $page = Page::factory()->draft()->create();

        $this->actingAs($editor)
            ->post(route('pages.approval.request', $page));

        $this->assertDatabaseCount('page_approvals', 1);
        $this->assertDatabaseHas('pages', ['id' => $page->id, 'pending_approval' => true]);
    }

    public function test_second_approval_request_cancels_previous(): void
    {
        $editor = $this->userWithPermission('page.update');
        $page = Page::factory()->draft()->create();

        // First request
        PageApproval::create([
            'page_id' => $page->id,
            'requested_by' => $editor->id,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        // Second request
        $this->actingAs($editor)
            ->post(route('pages.approval.request', $page));

        $this->assertDatabaseHas('page_approvals', [
            'page_id' => $page->id,
            'status' => 'cancelled',
        ]);

        $this->assertEquals(1, PageApproval::where('page_id', $page->id)->where('status', 'pending')->count());
    }

    public function test_unauthorized_user_cannot_request_approval(): void
    {
        $user = User::factory()->create();
        $page = Page::factory()->draft()->create();

        $this->actingAs($user)
            ->post(route('pages.approval.request', $page))
            ->assertForbidden();
    }

    public function test_guest_cannot_request_approval(): void
    {
        $page = Page::factory()->draft()->create();

        $this->post(route('pages.approval.request', $page))
            ->assertRedirect();
    }

    // =========================================================================
    // Approve
    // =========================================================================

    public function test_reviewer_can_approve_pending_page(): void
    {
        $reviewer = $this->userWithPermission('page.approve');
        $page = Page::factory()->draft()->create(['pending_approval' => true]);
        $approval = PageApproval::create([
            'page_id' => $page->id,
            'requested_by' => User::factory()->create()->id,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $this->actingAs($reviewer)
            ->post(route('pages.approvals.approve', $approval))
            ->assertRedirect();

        $this->assertDatabaseHas('page_approvals', [
            'id' => $approval->id,
            'status' => 'approved',
            'reviewed_by' => $reviewer->id,
        ]);
    }

    public function test_approval_publishes_page_when_approved(): void
    {
        $reviewer = $this->userWithPermission('page.approve');
        $page = Page::factory()->draft()->create(['pending_approval' => true]);
        $approval = PageApproval::create([
            'page_id' => $page->id,
            'requested_by' => User::factory()->create()->id,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $this->actingAs($reviewer)
            ->post(route('pages.approvals.approve', $approval), ['publish_now' => true]);

        $this->assertDatabaseHas('pages', [
            'id' => $page->id,
            'status' => PageStatus::Published->value,
        ]);
    }

    public function test_unauthorized_user_cannot_approve(): void
    {
        $user = User::factory()->create();
        $page = Page::factory()->draft()->create();
        $approval = PageApproval::create([
            'page_id' => $page->id,
            'requested_by' => User::factory()->create()->id,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('pages.approvals.approve', $approval))
            ->assertForbidden();
    }

    // =========================================================================
    // Reject
    // =========================================================================

    public function test_reviewer_can_reject_pending_page_with_reason(): void
    {
        $reviewer = $this->userWithPermission('page.approve');
        $page = Page::factory()->draft()->create(['pending_approval' => true]);
        $approval = PageApproval::create([
            'page_id' => $page->id,
            'requested_by' => User::factory()->create()->id,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $this->actingAs($reviewer)
            ->post(route('pages.approvals.reject', $approval), ['reason' => 'Content needs improvement.'])
            ->assertRedirect();

        $this->assertDatabaseHas('page_approvals', [
            'id' => $approval->id,
            'status' => 'rejected',
            'rejection_reason' => 'Content needs improvement.',
            'reviewed_by' => $reviewer->id,
        ]);
    }

    public function test_rejection_clears_pending_approval_flag(): void
    {
        $reviewer = $this->userWithPermission('page.approve');
        $page = Page::factory()->draft()->create(['pending_approval' => true]);
        $approval = PageApproval::create([
            'page_id' => $page->id,
            'requested_by' => User::factory()->create()->id,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $this->actingAs($reviewer)
            ->post(route('pages.approvals.reject', $approval), ['reason' => 'Not ready.']);

        $this->assertDatabaseHas('pages', ['id' => $page->id, 'pending_approval' => false]);
    }

    public function test_reject_requires_reason(): void
    {
        $reviewer = $this->userWithPermission('page.approve');
        $page = Page::factory()->draft()->create();
        $approval = PageApproval::create([
            'page_id' => $page->id,
            'requested_by' => User::factory()->create()->id,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $this->actingAs($reviewer)
            ->post(route('pages.approvals.reject', $approval), [])
            ->assertSessionHasErrors(['reason']);
    }

    // =========================================================================
    // Approvals index
    // =========================================================================

    public function test_approvals_index_shows_pending_pages(): void
    {
        $reviewer = $this->userWithPermission('page.approve');
        Page::factory()->draft()->create(['pending_approval' => true]);

        $this->actingAs($reviewer)
            ->get(route('pages.approvals.index'))
            ->assertOk();
    }

    public function test_unauthorized_user_cannot_view_approvals_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('pages.approvals.index'))
            ->assertForbidden();
    }
}
