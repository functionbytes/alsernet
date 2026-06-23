<?php

namespace Modules\HelpdeskCompliance\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Modules\HelpdeskCompliance\Http\Requests\Managers\RequestIndexRequest;
use Modules\HelpdeskCompliance\Models\ComplianceRequest;

/**
 * Manager view + JSON feed for the GDPR compliance request audit trail (the
 * record produced by RunComplianceCascade after each core GDPR deletion).
 */
class ComplianceController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', ComplianceRequest::class);

        return view('helpdeskcompliance::requests.index');
    }

    public function data(RequestIndexRequest $request): JsonResponse
    {
        $this->authorize('viewAny', ComplianceRequest::class);

        $requests = ComplianceRequest::query()
            ->with('customer')
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $requests->getCollection()->map(fn (ComplianceRequest $req): array => [
                'id' => $req->id,
                'customerId' => $req->customer_id,
                'customer' => $req->customer?->name,
                'type' => $req->type,
                'status' => $req->status,
                'modulesAffected' => $req->modules_affected ?? [],
                'resultSummary' => $req->result_summary,
                'completedAt' => $req->completed_at?->toIso8601String(),
                'createdAt' => $req->created_at?->toIso8601String(),
            ])->all(),
            'meta' => [
                'currentPage' => $requests->currentPage(),
                'lastPage' => $requests->lastPage(),
                'total' => $requests->total(),
            ],
        ]);
    }
}
