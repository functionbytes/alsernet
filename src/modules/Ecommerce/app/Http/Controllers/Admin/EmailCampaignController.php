<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Ecommerce\Http\Requests\Admin\StoreEmailCampaignRequest;
use Modules\Ecommerce\Jobs\SendCampaignJob;
use Modules\Ecommerce\Models\EmailCampaign;
use Modules\Ecommerce\Services\CustomerSegmentService;

class EmailCampaignController extends Controller
{
    public function __construct(private readonly CustomerSegmentService $segments)
    {
        // Authorize each method via middleware so route:list can resolve
        // the controller without invoking authorization in the constructor.
        $this->middleware('can:email-campaign.manage');
        $this->authorizeResource(EmailCampaign::class, 'campaign');
    }

    public function index(): View
    {
        $this->authorize('email-campaign.manage');
        $campaigns = EmailCampaign::query()->latest()->paginate(20);

        return view('ecommerce::email-campaigns.index', compact('campaigns'));
    }

    public function create(): View
    {
        $this->authorize('email-campaign.manage');
        $segmentCounts = [
            'all' => $this->segments->countSegment('all'),
            'vip' => $this->segments->countSegment('vip'),
            'inactive' => $this->segments->countSegment('inactive'),
            'new' => $this->segments->countSegment('new'),
            'recent_buyers' => $this->segments->countSegment('recent_buyers'),
        ];

        return view('ecommerce::email-campaigns.create', compact('segmentCounts'));
    }

    public function store(StoreEmailCampaignRequest $request): RedirectResponse
    {
        $this->authorize('email-campaign.manage');
        $validated = $request->validated();

        EmailCampaign::query()->create(array_merge($validated, [
            'status' => isset($validated['scheduled_at']) ? 'scheduled' : 'draft',
            'created_by' => auth()->id(),
        ]));

        return redirect()->route('ecommerce.email-campaigns.index')
            ->with('success', 'Campaña creada exitosamente.');
    }

    public function send(EmailCampaign $campaign): RedirectResponse
    {
        $this->authorize('email-campaign.manage');
        $this->authorize('update', $campaign);
        if (! in_array($campaign->status, ['draft', 'scheduled'])) {
            return back()->with('error', 'Solo se pueden enviar campañas en estado borrador o programado.');
        }

        SendCampaignJob::dispatch($campaign->id);

        return back()->with('success', 'Campaña encolada para envío.');
    }

    public function destroy(EmailCampaign $campaign): RedirectResponse
    {
        $this->authorize('email-campaign.manage');
        $campaign->delete();

        return back()->with('success', 'Campaña eliminada exitosamente.');
    }
}
