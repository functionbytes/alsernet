<?php

namespace Modules\Page\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Modules\Page\Events\PageApprovalApproved;
use Modules\Page\Events\PageApprovalRejected;
use Modules\Page\Events\PageApprovalRequested;
use Modules\Page\Models\Page;
use Modules\Page\Models\PageApproval;

class PageApprovalService
{
    public function __construct(
        private readonly PageService $pageService
    ) {}

    /**
     * Submit a page for review. Cancels any existing pending approvals first.
     */
    public function requestApproval(Page $page, User $requestedBy, ?string $notes = null): PageApproval
    {
        $page->approvals()->where('status', 'pending')->update(['status' => 'cancelled']);

        $approval = PageApproval::create([
            'page_id' => $page->id,
            'requested_by' => $requestedBy->id,
            'status' => 'pending',
            'notes' => $notes,
            'requested_at' => now(),
        ]);

        $page->update(['pending_approval' => true]);

        event(new PageApprovalRequested($page, $approval));

        return $approval;
    }

    /**
     * Approve a pending approval and optionally publish the page.
     */
    public function approve(PageApproval $approval, User $reviewer, bool $publishNow = true): void
    {
        $approval->update([
            'status' => 'approved',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        $approval->page->update(['pending_approval' => false]);

        if ($publishNow) {
            $this->pageService->publishPage($approval->page);
        }

        Log::info('Page approved', [
            'page_id' => $approval->page_id,
            'approved_by' => $reviewer->id,
            'approval_id' => $approval->id,
        ]);

        event(new PageApprovalApproved($approval->page->fresh(), $approval));
    }

    /**
     * Reject a pending approval with a required reason.
     */
    public function reject(PageApproval $approval, User $reviewer, string $reason): void
    {
        $approval->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $approval->page->update(['pending_approval' => false]);

        Log::info('Page rejected', [
            'page_id' => $approval->page_id,
            'rejected_by' => $reviewer->id,
            'reason' => $reason,
        ]);

        event(new PageApprovalRejected($approval->page->fresh(), $approval));
    }

    /**
     * List all pages currently awaiting approval.
     */
    public function pendingPages(): Collection
    {
        return Page::query()
            ->where('pending_approval', true)
            ->with(['latestApproval.requestedBy', 'user'])
            ->latest('updated_at')
            ->get();
    }
}
