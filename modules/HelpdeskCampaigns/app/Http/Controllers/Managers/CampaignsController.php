<?php

namespace Modules\HelpdeskCampaigns\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskCampaigns\Http\Requests\Managers\BulkActionCampaignRequest;
use Modules\HelpdeskCampaigns\Http\Requests\Managers\RejectCampaignRequest;
use Modules\HelpdeskCampaigns\Http\Requests\Managers\StoreCampaignRequest;
use Modules\HelpdeskCampaigns\Http\Requests\Managers\UpdateCampaignRequest;
use Modules\HelpdeskCampaigns\Models\Campaign;
use Modules\HelpdeskCampaigns\Models\CampaignTemplate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CampaignsController extends Controller
{
    /**
     * Display a listing of campaigns
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Campaign::class);

        // impressions_count / clicks_count are denormalized columns kept in sync
        // by UpdateCampaignImpressionCounters, so the listing reads them directly
        // instead of running two correlated COUNT subqueries per row (withCount).
        $query = Campaign::query()->orderBy('created_at', 'desc');

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
    public function store(StoreCampaignRequest $request): RedirectResponse
    {
        $this->authorize('create', Campaign::class);

        $campaign = Campaign::create($request->validated());

        return redirect()
            ->route('helpdesk.campaigns.edit', $campaign)
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
            'days_active' => $campaign->published_at ? (int) abs(now()->diffInDays($campaign->published_at)) : 0,
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
    public function update(UpdateCampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        $campaign->update($request->validated());

        Cache::forget("campaign_stats_{$campaign->id}");

        return back()->with('success', 'Campaña actualizada exitosamente');
    }

    /**
     * Delete the specified campaign
     */
    public function destroy(Campaign $campaign): RedirectResponse
    {
        $this->authorize('delete', $campaign);

        Cache::forget("campaign_stats_{$campaign->id}");

        $campaign->delete();

        return redirect()
            ->route('helpdesk.campaigns.index')
            ->with('success', 'Campaña eliminada exitosamente');
    }

    /**
     * Publish a campaign
     */
    public function publish(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        if ($campaign->requiresPendingApproval()) {
            return back()->with('error', 'La campaña requiere aprobación antes de publicarse.');
        }

        $campaign->publish();

        Cache::forget("campaign_stats_{$campaign->id}");

        return back()->with('success', 'Campaña publicada exitosamente');
    }

    /**
     * Pause a campaign
     */
    public function pause(Campaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        $campaign->pause();

        Cache::forget("campaign_stats_{$campaign->id}");

        return back()->with('success', 'Campaña pausada');
    }

    /**
     * Resume a campaign
     */
    public function resume(Campaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        $campaign->resume();

        Cache::forget("campaign_stats_{$campaign->id}");

        return back()->with('success', 'Campaña reanudada');
    }

    /**
     * End a campaign
     */
    public function end(Campaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        $campaign->end();

        Cache::forget("campaign_stats_{$campaign->id}");

        return back()->with('success', 'Campaña finalizada');
    }

    /**
     * Get campaign statistics (JSON)
     */
    public function statistics(Campaign $campaign): JsonResponse
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
     * Get impression timeline data grouped by day (JSON)
     */
    public function statisticsTimeline(Campaign $campaign): JsonResponse
    {
        $this->authorize('view', $campaign);

        // show.blade.php pide `?days=30` y consume {labels, impressions, clicks}
        // para Chart.js — devolver solo `timeline` dejaba el gráfico vacío.
        $days = max(1, min(365, (int) request('days', 30)));

        $rows = $campaign->impressions()
            ->selectRaw('DATE(viewed_at) as date, COUNT(*) as impressions, SUM(clicked_at IS NOT NULL) as clicks')
            ->where('viewed_at', '>=', now()->subDays($days)->startOfDay())
            ->groupByRaw('DATE(viewed_at)')
            ->orderBy('date')
            ->get();

        return response()->json([
            'campaign_id' => $campaign->id,
            'labels' => $rows->pluck('date')->all(),
            'impressions' => $rows->pluck('impressions')->map(fn ($v) => (int) $v)->all(),
            'clicks' => $rows->pluck('clicks')->map(fn ($v) => (int) $v)->all(),
            // Forma anterior, por si algún consumidor externo la usaba.
            'timeline' => $rows,
        ]);
    }

    /**
     * Get recent activity log for a campaign (JSON)
     */
    public function activity(Campaign $campaign): JsonResponse
    {
        $this->authorize('view', $campaign);

        $recent = $campaign->impressions()
            ->orderByDesc('viewed_at')
            ->limit(50)
            ->get(['impression_id', 'device_type', 'country', 'viewed_at', 'clicked_at']);

        return response()->json([
            'campaign_id' => $campaign->id,
            'activity' => $recent,
        ]);
    }

    /**
     * Export impression statistics as CSV
     */
    public function exportStatistics(Campaign $campaign): StreamedResponse
    {
        $this->authorize('view', $campaign);

        // Antes cargaba TODAS las impresiones en memoria y concatenaba el CSV
        // completo en un string PHP — una campaña popular con cientos de
        // miles de filas podia agotar memoria o generar timeouts. Mismo
        // patron cursor()+streamDownload() ya usado en HelpdeskReportsController::export().
        // page_url y browser vienen del endpoint público de tracking (sin
        // auth), así que se neutraliza el CSV formula injection: un valor que
        // empiece por =,+,-,@ se activaría como fórmula al abrir el CSV en Excel.
        $csvSafe = function ($value): string {
            $value = (string) $value;

            return preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
        };

        return response()->streamDownload(function () use ($campaign, $csvSafe) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['impression_id', 'device_type', 'browser', 'country', 'page_url', 'viewed_at', 'clicked_at']);

            $campaign->impressions()
                ->orderByDesc('viewed_at')
                ->select(['impression_id', 'device_type', 'browser', 'country', 'page_url', 'viewed_at', 'clicked_at'])
                ->cursor()
                ->each(function ($impression) use ($handle, $csvSafe) {
                    fputcsv($handle, [
                        $impression->impression_id,
                        $csvSafe($impression->device_type),
                        $csvSafe($impression->browser),
                        $csvSafe($impression->country),
                        $csvSafe($impression->page_url),
                        $impression->viewed_at,
                        $impression->clicked_at,
                    ]);
                });

            fclose($handle);
        }, "campaign-{$campaign->id}-stats.csv", [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Bulk action on campaigns (activate, pause, delete, etc.)
     */
    public function bulkAction(BulkActionCampaignRequest $request): JsonResponse
    {
        $this->authorize('manage', Campaign::class);

        $validated = $request->validated();

        $campaigns = Campaign::query()->whereIn('id', $validated['ids'])->get();

        foreach ($campaigns as $campaign) {
            match ($validated['action']) {
                'delete' => $campaign->delete(),
                'activate' => $campaign->publish(),
                'pause' => $campaign->pause(),
                'end' => $campaign->end(),
            };
        }

        return response()->json([
            'success' => true,
            'message' => 'Acción aplicada a '.$campaigns->count().' campañas.',
        ]);
    }

    /**
     * Submit campaign for approval
     */
    public function submitForApproval(Campaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        $campaign->update([
            'status' => 'pending_approval',
            'metadata' => array_merge($campaign->metadata ?? [], [
                'submitted_at' => now()->toIso8601String(),
                'submitted_by_user_id' => auth()->id(),
            ]),
        ]);

        Cache::forget("campaign_stats_{$campaign->id}");

        return back()->with('success', 'Campaña enviada para aprobación');
    }

    /**
     * Approve a campaign
     */
    public function approve(Campaign $campaign): RedirectResponse
    {
        $this->authorize('manage', Campaign::class);

        $publishedAt = $campaign->published_at;
        $status = ($publishedAt && $publishedAt->isFuture()) ? 'scheduled' : 'active';

        $campaign->update([
            'status' => $status,
            'approved_at' => now(),
            'approved_by_user_id' => auth()->id(),
            'metadata' => array_merge($campaign->metadata ?? [], [
                'approved_at' => now()->toIso8601String(),
            ]),
        ]);

        Cache::forget("campaign_stats_{$campaign->id}");

        return back()->with('success', 'Campaña aprobada');
    }

    /**
     * Reject a campaign
     */
    public function reject(RejectCampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('manage', Campaign::class);

        $reason = strip_tags($request->validated('reason'));

        $campaign->update([
            'status' => 'draft',
            'metadata' => array_merge($campaign->metadata ?? [], [
                'rejection_reason' => $reason,
                'rejected_at' => now()->toIso8601String(),
                'rejected_by_user_id' => auth()->id(),
            ]),
        ]);

        Cache::forget("campaign_stats_{$campaign->id}");

        return back()->with('success', 'Campaña rechazada');
    }

    /**
     * Duplicate a campaign
     */
    public function duplicate(Campaign $campaign): RedirectResponse
    {
        $this->authorize('create', Campaign::class);

        $newCampaign = DB::connection('helpdesk')->transaction(function () use ($campaign): Campaign {
            $copy = $campaign->replicate();
            $copy->name = $campaign->name.' (Copia)';
            $copy->status = 'draft';
            $copy->published_at = null;
            $copy->ends_at = null;
            // La copia arranca sin estadísticas — replicate() arrastraría las del original.
            $copy->impressions_count = 0;
            $copy->clicks_count = 0;
            $copy->save();

            // Clonar las variantes A/B (se perdían al duplicar), con contadores a cero.
            foreach ($campaign->variants as $variant) {
                $variantCopy = $variant->replicate();
                $variantCopy->campaign_id = $copy->id;
                $variantCopy->impressions_count = 0;
                $variantCopy->clicks_count = 0;
                $variantCopy->save();
            }

            return $copy;
        });

        return redirect()
            ->route('helpdesk.campaigns.edit', $newCampaign)
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
