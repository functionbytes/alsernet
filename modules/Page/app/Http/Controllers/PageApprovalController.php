<?php

namespace Modules\Page\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Page\Models\Page;
use Modules\Page\Models\PageApproval;
use Modules\Page\Services\PageApprovalService;

class PageApprovalController extends Controller
{
    public function __construct(
        private readonly PageApprovalService $approvalService
    ) {}

    /**
     * List all approvals with search and status filter.
     */
    public function index(Request $request): View
    {
        $this->authorize('approve', Page::class);

        $query = PageApproval::query()
            ->with(['page', 'requestedBy', 'reviewedBy']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('page', fn ($q) => $q->where('title', 'like', "%{$search}%"));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $approvals = $query->latest('requested_at')->paginate(25);

        $statsRaw = PageApproval::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending")
            ->selectRaw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved")
            ->selectRaw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected")
            ->first();

        $stats = [
            'total' => (int) $statsRaw->total,
            'pending' => (int) $statsRaw->pending,
            'approved' => (int) $statsRaw->approved,
            'rejected' => (int) $statsRaw->rejected,
        ];

        return view('page::pages.approvals.index', compact('approvals', 'stats'));
    }

    /**
     * Submit a page for approval (editor action).
     */
    public function request(Page $page, Request $request): RedirectResponse
    {
        $this->authorize('update', $page);

        $validated = $request->validate(['notes' => 'nullable|string|max:500']);

        $this->approvalService->requestApproval($page, auth()->user(), $validated['notes'] ?? null);

        return back()->with('success', 'Página enviada a revisión correctamente.');
    }

    /**
     * Approve a pending approval and publish the page.
     */
    public function approve(PageApproval $approval, Request $request): RedirectResponse
    {
        $this->authorize('approve', Page::class);

        $publishNow = $request->boolean('publish_now', true);

        $this->approvalService->approve($approval, auth()->user(), $publishNow);

        return back()->with('success', 'Página aprobada y publicada.');
    }

    /**
     * Reject a pending approval with a reason.
     */
    public function reject(PageApproval $approval, Request $request): RedirectResponse
    {
        $this->authorize('approve', Page::class);

        $validated = $request->validate(['reason' => 'required|string|max:1000']);

        $this->approvalService->reject($approval, auth()->user(), $validated['reason']);

        return back()->with('success', 'Página rechazada. El autor ha sido notificado.');
    }

    /**
     * Bulk approve or delete approvals.
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $this->authorize('approve', Page::class);

        $validated = $request->validate([
            'action' => ['required', 'string', 'in:approve,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $approvals = PageApproval::whereIn('id', $validated['ids'])->get();
        $count = 0;

        foreach ($approvals as $approval) {
            match ($validated['action']) {
                'approve' => $this->approvalService->approve($approval, auth()->user(), true),
                'delete' => $approval->delete(),
            };
            $count++;
        }

        return response()->json(['success' => true, 'count' => $count]);
    }
}
