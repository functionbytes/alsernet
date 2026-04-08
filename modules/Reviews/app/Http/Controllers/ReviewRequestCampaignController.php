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

        $stats = [
            'total' => ReviewRequestCampaign::count(),
            'active' => ReviewRequestCampaign::active()->count(),
            'scheduled' => ReviewRequestCampaign::scheduled()->count(),
            'draft' => ReviewRequestCampaign::draft()->count(),
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

        $campaigns = ReviewRequestCampaign::query()->whereIn('id', $validated['ids'])->get();
        $deleted = 0;

        foreach ($campaigns as $campaign) {
            $campaign->delete();
            $deleted++;
        }

        return response()->json([
            'success' => true,
            'message' => "Se eliminaron {$deleted} campañas correctamente.",
            'deleted' => $deleted,
        ]);
    }

    public function sendToList(Request $request, ReviewRequestCampaign $campaign): JsonResponse
    {
        $validated = $request->validate([
            'recipients' => ['required', 'array', 'min:1', 'max:100'],
            'recipients.*.name' => ['required', 'string', 'max:150'],
            'recipients.*.email' => ['required', 'email', 'max:200'],
        ]);

        $sends = collect($validated['recipients'])->map(function (array $recipient) use ($campaign) {
            return ReviewRequestSend::create([
                'campaign_id' => $campaign->id,
                'customer_name' => $recipient['name'],
                'customer_email' => $recipient['email'],
                'status' => 'pending',
            ]);
        });

        $sends->each(fn (ReviewRequestSend $send) => SendReviewRequestJob::dispatch($send));

        return response()->json([
            'success' => true,
            'message' => "Se han encolado {$sends->count()} correos.",
            'dispatched' => $sends->count(),
        ]);
    }

    public function stats(ReviewRequestCampaign $campaign): JsonResponse
    {
        $total = $campaign->sends()->count();
        $sent = $campaign->sends()->sent()->count();
        $failed = $campaign->sends()->where('status', 'failed')->count();
        $opened = $campaign->sends()->whereNotNull('opened_at')->count();

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
