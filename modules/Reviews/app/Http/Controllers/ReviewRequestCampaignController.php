<?php

namespace Modules\Reviews\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Reviews\Jobs\SendReviewRequestJob;
use Modules\Reviews\Models\ReviewGoogleLocation;
use Modules\Reviews\Models\ReviewRequestCampaign;
use Modules\Reviews\Models\ReviewRequestSend;

class ReviewRequestCampaignController extends Controller
{
    public function index(Request $request): View
    {
        $campaigns = ReviewRequestCampaign::query()
            ->with('location:id,name')
            ->withCount(['sends', 'sends as sent_sends_count' => fn ($q) => $q->sent()])
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->search.'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $statsRow = ReviewRequestCampaign::query()
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = \'active\' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = \'scheduled\' THEN 1 ELSE 0 END) as scheduled,
                SUM(CASE WHEN status = \'draft\' THEN 1 ELSE 0 END) as draft
            ')
            ->first();

        $stats = [
            'total' => (int) $statsRow->total,
            'active' => (int) $statsRow->active,
            'scheduled' => (int) $statsRow->scheduled,
            'draft' => (int) $statsRow->draft,
        ];

        return view('reviews::campaigns.index', compact('campaigns', 'stats'));
    }

    public function create(): View
    {
        $locations = $this->userLocations();

        return view('reviews::campaigns.create', compact('locations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'location_id' => ['required', 'integer', 'exists:review_google_locations,id'],
            'name' => ['required', 'string', 'max:150'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string'],
            'review_url' => ['required', 'url', 'max:500'],
            'status' => ['nullable', 'string', 'in:draft,active'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        ReviewRequestCampaign::create($validated);

        return redirect()->route('reviews.campaigns.index')
            ->with('success', 'Campaña creada correctamente.');
    }

    public function edit(ReviewRequestCampaign $campaign): View
    {
        $locations = $this->userLocations();

        return view('reviews::campaigns.edit', compact('campaign', 'locations'));
    }

    public function update(Request $request, ReviewRequestCampaign $campaign): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string'],
            'review_url' => ['required', 'url', 'max:500'],
            'status' => ['nullable', 'string', 'in:draft,active,paused'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $campaign->update($validated);

        return redirect()->route('reviews.campaigns.index')
            ->with('success', 'Campaña actualizada correctamente.');
    }

    public function destroy(ReviewRequestCampaign $campaign): JsonResponse
    {
        $campaign->delete();

        return response()->json([
            'success' => true,
            'message' => 'Campaña eliminada correctamente.',
        ]);
    }

    public function toggle(ReviewRequestCampaign $campaign): JsonResponse
    {
        $campaign->update([
            'status' => $campaign->isActive() ? 'paused' : 'active',
        ]);

        return response()->json([
            'success' => true,
            'status' => $campaign->status,
            'message' => $campaign->isActive() ? 'Campaña activada.' : 'Campaña pausada.',
        ]);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:review_request_campaigns,id'],
        ]);

        $ids = $validated['ids'];

        ReviewRequestCampaign::query()->whereIn('id', $ids)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Se eliminaron '.count($ids).' campañas correctamente.',
            'deleted' => count($ids),
        ]);
    }

    public function sendToList(Request $request, ReviewRequestCampaign $campaign): JsonResponse
    {
        $validated = $request->validate([
            'recipients' => ['required', 'array', 'min:1', 'max:100'],
            'recipients.*.name' => ['required', 'string', 'max:150'],
            'recipients.*.email' => ['required', 'email', 'max:200'],
        ]);

        $now = now();
        $rows = collect($validated['recipients'])->map(function (array $recipient) use ($campaign, $now) {
            return [
                'campaign_id' => $campaign->id,
                'customer_name' => $recipient['name'],
                'customer_email' => $recipient['email'],
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();

        ReviewRequestSend::query()->insert($rows);

        $sends = ReviewRequestSend::query()
            ->where('campaign_id', $campaign->id)
            ->whereIn('customer_email', collect($validated['recipients'])->pluck('email'))
            ->where('created_at', $now)
            ->get();

        $sends->each(fn (ReviewRequestSend $send) => SendReviewRequestJob::dispatch($send));

        return response()->json([
            'success' => true,
            'message' => "Se han encolado {$sends->count()} correos.",
            'dispatched' => $sends->count(),
        ]);
    }

    public function stats(ReviewRequestCampaign $campaign): JsonResponse
    {
        $statsRow = $campaign->sends()
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = \'sent\' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = \'failed\' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) as opened
            ')
            ->first();

        $total = (int) $statsRow->total;
        $sent = (int) $statsRow->sent;
        $failed = (int) $statsRow->failed;
        $opened = (int) $statsRow->opened;

        return response()->json([
            'success' => true,
            'stats' => [
                'total' => $total,
                'sent' => $sent,
                'failed' => $failed,
                'opened' => $opened,
                'sent_count' => $campaign->sent_count,
                'open_rate' => $sent > 0 ? round($opened / $sent * 100, 1) : 0,
                'delivery_rate' => $total > 0 ? round($sent / $total * 100, 1) : 0,
            ],
        ]);
    }

    public function qrCode(ReviewRequestCampaign $campaign): View
    {
        $campaign->load('location:id,name');

        return view('reviews::campaigns.qr-code', compact('campaign'));
    }

    private function userLocations()
    {
        return ReviewGoogleLocation::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
