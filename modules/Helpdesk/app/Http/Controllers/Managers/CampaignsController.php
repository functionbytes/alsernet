<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Campaign\Http\Requests\StoreCampaignRequest;
use Modules\Campaign\Http\Requests\UpdateCampaignRequest;
use Modules\Helpdesk\Models\Campaign;
use Modules\Helpdesk\Models\CampaignTemplate;

class CampaignsController extends Controller
{
    /**
     * Display a listing of campaigns
     */
    public function index(Request $request)
    {
        $this->authorize('view', Campaign::class);

        $query = Campaign::query()
            ->with(['impressions' => function ($q) {
                $q->select('campaign_id')->selectRaw('COUNT(*) as count')->groupBy('campaign_id');
            }])
            ->orderBy('created_at', 'desc');

        // Filtering
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%")
                ->orWhere('description', 'like', "%{$request->search}%");
        }

        $campaigns = $query->paginate(20);

        return view('helpdesk::managers.helpdesk.campaigns.index', [
            'campaigns' => $campaigns,
            'filters' => $request->only(['status', 'type', 'search']),
            'statuses' => ['draft' => 'Borrador', 'scheduled' => 'Programada', 'active' => 'Activa', 'ended' => 'Finalizada', 'paused' => 'Pausada'],
            'types' => ['popup' => 'Pop-up', 'banner' => 'Banner', 'slide-in' => 'Slide-in', 'full-screen' => 'Pantalla Completa'],
        ]);
    }

    /**
     * Show the form for creating a new campaign
     */
    public function create()
    {
        $this->authorize('create', Campaign::class);

        $templates = CampaignTemplate::all();

        return view('helpdesk::managers.helpdesk.campaigns.create', [
            'templates' => $templates,
        ]);
    }

    /**
     * Store a newly created campaign
     */
    public function store(StoreCampaignRequest $request)
    {
        $this->authorize('create', Campaign::class);

        $campaign = Campaign::create($request->validated());

        return redirect()
            ->route('manager.helpdesk.campaigns.edit', $campaign)
            ->with('success', 'Campaña creada exitosamente');
    }

    /**
     * Display the specified campaign
     */
    public function show(Campaign $campaign)
    {
        $this->authorize('view', $campaign);

        // Load impressions for statistics
        $campaign->load(['impressions' => fn ($q) => $q->latest('created_at')->limit(100)]);

        // Calculate performance metrics
        $stats = [
            'total_impressions' => $campaign->impressions_count,
            'total_clicks' => $campaign->conversions_count,
            'ctr' => $campaign->ctr,
            'daily_avg' => $campaign->average_daily_impressions,
            'days_active' => $campaign->published_at ? now()->diffInDays($campaign->published_at) : 0,
        ];

        return view('helpdesk::managers.helpdesk.campaigns.show', [
            'campaign' => $campaign,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for editing the specified campaign
     */
    public function edit(Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        $templates = CampaignTemplate::all();

        return view('helpdesk::managers.helpdesk.campaigns.edit', [
            'campaign' => $campaign,
            'templates' => $templates,
        ]);
    }

    /**
     * Update the specified campaign
     */
    public function update(UpdateCampaignRequest $request, Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        $campaign->update($request->validated());

        return back()->with('success', 'Campaña actualizada exitosamente');
    }

    /**
     * Delete the specified campaign
     */
    public function destroy(Campaign $campaign)
    {
        $this->authorize('delete', $campaign);

        $campaign->delete();

        return redirect()
            ->route('manager.helpdesk.campaigns.index')
            ->with('success', 'Campaña eliminada exitosamente');
    }

    /**
     * Publish a campaign
     */
    public function publish(Request $request, Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        $campaign->publish();

        return back()->with('success', 'Campaña publicada exitosamente');
    }

    /**
     * Pause a campaign
     */
    public function pause(Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        $campaign->pause();

        return back()->with('success', 'Campaña pausada');
    }

    /**
     * Resume a campaign
     */
    public function resume(Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        $campaign->resume();

        return back()->with('success', 'Campaña reanudada');
    }

    /**
     * End a campaign
     */
    public function end(Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        $campaign->end();

        return back()->with('success', 'Campaña finalizada');
    }

    /**
     * Get campaign statistics
     */
    public function statistics(Campaign $campaign)
    {
        $this->authorize('view', $campaign);

        $impressions = $campaign->impressions()->count();
        $clicks = $campaign->impressions()->whereNotNull('clicked_at')->count();
        $ctr = $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0;

        return response()->json([
            'campaign_id' => $campaign->id,
            'impressions' => $impressions,
            'clicks' => $clicks,
            'ctr' => $ctr.'%',
            'daily_impressions' => $campaign->average_daily_impressions,
            'status' => $campaign->status_label,
            'created_at' => $campaign->created_at,
            'published_at' => $campaign->published_at,
        ]);
    }

    /**
     * Duplicate a campaign
     */
    public function duplicate(Campaign $campaign)
    {
        $this->authorize('create', Campaign::class);

        $newCampaign = $campaign->replicate();
        $newCampaign->name = $campaign->name.' (Copia)';
        $newCampaign->status = 'draft';
        $newCampaign->published_at = null;
        $newCampaign->ends_at = null;
        $newCampaign->save();

        return redirect()
            ->route('manager.helpdesk.campaigns.edit', $newCampaign)
            ->with('success', 'Campaña duplicada exitosamente');
    }

    /**
     * Show campaign templates library
     */
    public function templates()
    {
        $this->authorize('create', Campaign::class);

        $templates = CampaignTemplate::all();

        return view('helpdesk::managers.helpdesk.campaigns.templates', [
            'templates' => $templates,
        ]);
    }
}
