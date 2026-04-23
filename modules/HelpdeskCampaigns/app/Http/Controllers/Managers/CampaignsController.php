<?php

namespace Modules\HelpdeskCampaigns\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskCampaigns\Http\Requests\StoreCampaignRequest;
use Modules\HelpdeskCampaigns\Http\Requests\UpdateCampaignRequest;
use Modules\HelpdeskCampaigns\Models\Campaign;
use Modules\HelpdeskCampaigns\Models\CampaignTemplate;

class CampaignsController extends Controller
{
    /**
     * Display a listing of campaigns
     */
    public function index(Request $request)
    {
        $this->authorize('view', Campaign::class);

        $query = Campaign::query()
            ->withCount([
                'impressions',
                'impressions as clicks_count' => fn ($q) => $q->whereNotNull('clicked_at'),
            ])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        $campaigns = $query->paginate(20);

        return view('helpdeskcampaigns::managers.campaigns.index', [
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

        $templates = CampaignTemplate::select(['id', 'name', 'type'])->limit(100)->get();

        return view('helpdeskcampaigns::managers.campaigns.create', [
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
            ->route('manager.helpdesk-campaigns.edit', $campaign)
            ->with('success', 'Campaña creada exitosamente');
    }

    /**
     * Display the specified campaign
     */
    public function show(Campaign $campaign)
    {
        $this->authorize('view', $campaign);

        $agg = $campaign->impressions()
            ->selectRaw('COUNT(*) as total, SUM(clicked_at IS NOT NULL) as clicks')
            ->first();

        $impressionsCount = (int) ($agg->total ?? 0);
        $clicksCount = (int) ($agg->clicks ?? 0);
        $ctr = $impressionsCount > 0 ? round($clicksCount * 100 / $impressionsCount, 1) : 0;

        $stats = [
            'total_impressions' => $impressionsCount,
            'total_clicks' => $clicksCount,
            'ctr' => $ctr,
            'daily_avg' => $campaign->average_daily_impressions,
            'days_active' => $campaign->published_at ? now()->diffInDays($campaign->published_at) : 0,
        ];

        return view('helpdeskcampaigns::managers.campaigns.show', [
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

        $templates = CampaignTemplate::select(['id', 'name', 'type'])->limit(100)->get();

        return view('helpdeskcampaigns::managers.campaigns.edit', [
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

        Cache::forget("campaign_stats_{$campaign->id}");

        return back()->with('success', 'Campaña actualizada exitosamente');
    }

    /**
     * Delete the specified campaign
     */
    public function destroy(Campaign $campaign)
    {
        $this->authorize('delete', $campaign);

        Cache::forget("campaign_stats_{$campaign->id}");

        $campaign->delete();

        return redirect()
            ->route('manager.helpdesk-campaigns.index')
            ->with('success', 'Campaña eliminada exitosamente');
    }

    /**
     * Publish a campaign
     */
    public function publish(Request $request, Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        $campaign->publish();

        Cache::forget("campaign_stats_{$campaign->id}");

        return back()->with('success', 'Campaña publicada exitosamente');
    }

    /**
     * Pause a campaign
     */
    public function pause(Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        $campaign->pause();

        Cache::forget("campaign_stats_{$campaign->id}");

        return back()->with('success', 'Campaña pausada');
    }

    /**
     * Resume a campaign
     */
    public function resume(Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        $campaign->resume();

        Cache::forget("campaign_stats_{$campaign->id}");

        return back()->with('success', 'Campaña reanudada');
    }

    /**
     * End a campaign
     */
    public function end(Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        $campaign->end();

        Cache::forget("campaign_stats_{$campaign->id}");

        return back()->with('success', 'Campaña finalizada');
    }

    /**
     * Get campaign statistics
     */
    public function statistics(Campaign $campaign)
    {
        $this->authorize('view', $campaign);

        $stats = Cache::remember("campaign_stats_{$campaign->id}", 300, function () use ($campaign) {
            $agg = $campaign->impressions()
                ->selectRaw('COUNT(*) as total, SUM(clicked_at IS NOT NULL) as clicks')
                ->first();

            $impressions = (int) ($agg->total ?? 0);
            $clicks = (int) ($agg->clicks ?? 0);
            $ctr = $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0;

            return [
                'impressions' => $impressions,
                'clicks' => $clicks,
                'ctr' => $ctr.'%',
                'daily_impressions' => $campaign->average_daily_impressions,
                'status' => $campaign->status_label,
                'created_at' => $campaign->created_at,
                'published_at' => $campaign->published_at,
            ];
        });

        return response()->json(array_merge(
            ['campaign_id' => $campaign->id],
            $stats
        ));
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
            ->route('manager.helpdesk-campaigns.edit', $newCampaign)
            ->with('success', 'Campaña duplicada exitosamente');
    }

    /**
     * Show campaign templates library
     */
    public function templates()
    {
        $this->authorize('create', Campaign::class);

        $templates = CampaignTemplate::select(['id', 'name', 'type'])->limit(100)->get();

        return view('helpdeskcampaigns::managers.campaigns.templates', [
            'templates' => $templates,
        ]);
    }
}
