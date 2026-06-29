<?php

declare(strict_types=1);

namespace Modules\Engagement\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Modules\Engagement\Concerns\RecordsAudit;
use Modules\Engagement\Models\EmailCampaign;
use Modules\Engagement\Services\EmailCampaignService;
use Modules\Engagement\Traits\SoftDeletesActions;
use Modules\Helpdesk\Models\Inbox;

class EmailCampaignController extends Controller
{
    use RecordsAudit;
    use SoftDeletesActions;

    public function __construct()
    {
        $this->middleware('can:engagement.email_campaigns.view')->only('page', 'index', 'show');
        $this->middleware('can:engagement.email_campaigns.create')->only('store');
        $this->middleware('can:engagement.email_campaigns.update')->only('update', 'send', 'syncStats');
        $this->middleware('can:engagement.email_campaigns.delete')->only('destroy');
        $this->middleware('can:engagement.email_campaigns.view')->only('trashed');
        $this->middleware('can:engagement.email_campaigns.update')->only('restore');
    }

    public function index(Request $request): JsonResponse
    {
        $rows = EmailCampaign::query()
            ->when($request->input('inbox_id'), fn ($q, $id) => $q->forInbox((int) $id))
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'inbox_id' => ['required', 'integer', 'exists:helpdesk.helpdesk_inboxes,id'],
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'from_email' => ['nullable', 'email', 'max:255'],
            'html_content' => ['nullable', 'string'],
            'text_content' => ['nullable', 'string'],
            'provider' => ['required', 'in:mailchimp,sendgrid'],
            'provider_list_id' => ['nullable', 'string', 'max:255'],
            'segment_conditions' => ['nullable', 'array'],
            'scheduled_at' => ['nullable', 'date'],
            'status' => ['required', 'in:draft,scheduled,sent'],
        ]);

        $campaign = EmailCampaign::create($validated);
        $this->clearCache($validated['inbox_id']);

        return response()->json(['success' => true, 'data' => $campaign], 201);
    }

    public function show(int $id): JsonResponse
    {
        $campaign = EmailCampaign::findOrFail($id);

        return response()->json(['success' => true, 'data' => $campaign]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $campaign = EmailCampaign::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'subject' => ['sometimes', 'string', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'from_email' => ['nullable', 'email', 'max:255'],
            'html_content' => ['nullable', 'string'],
            'text_content' => ['nullable', 'string'],
            'provider' => ['sometimes', 'in:mailchimp,sendgrid'],
            'provider_list_id' => ['nullable', 'string', 'max:255'],
            'segment_conditions' => ['nullable', 'array'],
            'scheduled_at' => ['nullable', 'date'],
            'status' => ['sometimes', 'in:draft,scheduled,sent'],
        ]);

        $campaign->update($validated);
        $this->clearCache($campaign->inbox_id);

        return response()->json(['success' => true, 'data' => $campaign]);
    }

    public function destroy(int $id): JsonResponse
    {
        $campaign = EmailCampaign::findOrFail($id);
        $inboxId = $campaign->inbox_id;
        $campaign->delete();
        $this->clearCache($inboxId);

        return response()->json(['success' => true]);
    }

    public function send(int $id, EmailCampaignService $service): JsonResponse
    {
        $campaign = EmailCampaign::findOrFail($id);

        if ($campaign->status === 'sent') {
            return response()->json(['success' => false, 'message' => 'La campaña ya fue enviada.'], 422);
        }

        try {
            $service->send($campaign);
            $this->clearCache($campaign->inbox_id);

            return response()->json(['success' => true, 'data' => $campaign->fresh()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function syncStats(int $id, EmailCampaignService $service): JsonResponse
    {
        $campaign = EmailCampaign::findOrFail($id);

        if (! $campaign->provider_campaign_id) {
            return response()->json(['success' => false, 'message' => 'La campaña no tiene ID de proveedor.'], 422);
        }

        try {
            $service->syncStats($campaign);
            $this->clearCache($campaign->inbox_id);

            return response()->json(['success' => true, 'data' => $campaign->fresh()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function page(): View
    {
        $inboxes = Inbox::query()->where('is_active', true)->get(['id', 'name']);

        return view('engagement::settings.engagement.email-campaigns', compact('inboxes'));
    }

    protected function getModelClass(): string
    {
        return EmailCampaign::class;
    }

    private function clearCache(int $inboxId): void
    {
        Cache::forget("engagement:inbox:{$inboxId}:email-campaigns");
    }
}
