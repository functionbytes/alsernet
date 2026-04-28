<?php

namespace Modules\Campaign\Http\Controllers\Managers\Campaigns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Modules\Campaign\Library\BaseCampaign;
use Modules\Campaign\Models\Campaign;
use Modules\Campaign\Models\CampaignMaillist;
use Modules\Campaign\Models\Template\Template;
use Modules\Campaign\Services\CampaignService;
use Modules\CampaignSendingServers\Models\TrackingDomain;

/**
 * Slim CampaignsController. Cubre las acciones del flujo principal:
 *   index, create, store, show, edit, update, destroy
 *   confirm (lanzar), pause, resume
 *   recipients (asociar listas/segmentos)
 *   tracking-logs (vista de envíos)
 *
 * El legacy (60+ rutas con métricas avanzadas, copy, export, webhooks UI)
 * vive en CampaignsControllerLegacy.php; portar bajo demanda.
 */
class CampaignsController extends Controller
{
    public function __construct(
        protected CampaignService $service,
    ) {}

    public function index(Request $request): View
    {
        $campaigns = Campaign::query()
            ->when($request->query('q'), fn ($q, $kw) => $q->where('name', 'like', "%{$kw}%")->orWhere('subject', 'like', "%{$kw}%"))
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('campaign::manager.campaigns.index', [
            'campaigns' => $campaigns,
            'statuses' => $this->statuses(),
        ]);
    }

    public function create(): View
    {
        return view('campaign::manager.campaigns.create', [
            'mailLists' => CampaignMaillist::orderBy('name')->get(),
            'templates' => Template::orderBy('name')->get(),
            'trackingDomains' => TrackingDomain::where('status', TrackingDomain::STATUS_VERIFIED)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:998'],
            'from_email' => ['required', 'email'],
            'from_name' => ['required', 'string', 'max:255'],
            'reply_to' => ['nullable', 'email'],
            'type' => ['nullable', 'in:regular,plain-text'],
            'default_maillist_id' => ['nullable', 'exists:campaign_maillists,id'],
            'tracking_domain_id' => ['nullable', 'exists:campaign_sending_server_tracking_domains,id'],
            'template_id' => ['nullable', 'exists:campaign_templates,id'],
            'track_open' => ['nullable', 'boolean'],
            'track_click' => ['nullable', 'boolean'],
            'sign_dkim' => ['nullable', 'boolean'],
        ]);

        $campaign = $this->service->create($data);

        return redirect()
            ->route('manager.campaigns.show', $campaign->uid)
            ->with('success', 'Campaña creada.');
    }

    public function show(string $uid): View
    {
        $campaign = Campaign::where('uid', $uid)->firstOrFail();

        return view('campaign::manager.campaigns.show', [
            'campaign' => $campaign,
            'stats' => $this->service->getStatistics($campaign),
        ]);
    }

    public function edit(string $uid): View
    {
        $campaign = Campaign::where('uid', $uid)->firstOrFail();

        return view('campaign::manager.campaigns.edit', [
            'campaign' => $campaign,
            'mailLists' => CampaignMaillist::orderBy('name')->get(),
            'templates' => Template::orderBy('name')->get(),
            'trackingDomains' => TrackingDomain::where('status', TrackingDomain::STATUS_VERIFIED)->get(),
        ]);
    }

    public function update(Request $request, string $uid): RedirectResponse
    {
        $campaign = Campaign::where('uid', $uid)->firstOrFail();

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'subject' => ['sometimes', 'required', 'string', 'max:998'],
            'from_email' => ['sometimes', 'required', 'email'],
            'from_name' => ['sometimes', 'required', 'string', 'max:255'],
            'reply_to' => ['nullable', 'email'],
            'preheader' => ['nullable', 'string', 'max:255'],
            'plain' => ['nullable', 'string'],
            'default_maillist_id' => ['nullable', 'exists:campaign_maillists,id'],
            'tracking_domain_id' => ['nullable', 'exists:campaign_sending_server_tracking_domains,id'],
            'template_id' => ['nullable', 'exists:campaign_templates,id'],
            'track_open' => ['nullable', 'boolean'],
            'track_click' => ['nullable', 'boolean'],
            'sign_dkim' => ['nullable', 'boolean'],
            'run_at' => ['nullable', 'date', 'after:now'],
        ]);

        $this->service->update($campaign, $data);

        return redirect()
            ->route('manager.campaigns.show', $campaign->uid)
            ->with('success', 'Campaña actualizada.');
    }

    public function destroy(string $uid): RedirectResponse
    {
        $campaign = Campaign::where('uid', $uid)->firstOrFail();
        $campaign->deleteAndCleanup();

        return redirect()
            ->route('manager.campaigns.index')
            ->with('success', 'Campaña eliminada.');
    }

    /**
     * Asociar listas / segmentos a la campaña (recipients selector).
     */
    public function recipients(Request $request, string $uid)
    {
        $campaign = Campaign::where('uid', $uid)->firstOrFail();

        if ($request->isMethod('POST')) {
            $data = $request->validate([
                'lists' => ['required', 'array', 'min:1'],
                'lists.*.mail_list_id' => ['required', 'exists:campaign_maillists,id'],
                'lists.*.segment_id' => ['nullable', 'exists:campaign_segments,id'],
            ]);

            \DB::transaction(function () use ($campaign, $data): void {
                \DB::table('campaign_lists_segments')->where('campaign_id', $campaign->id)->delete();
                $now = now();
                $rows = array_map(fn ($r) => [
                    'campaign_id' => $campaign->id,
                    'mail_list_id' => $r['mail_list_id'],
                    'segment_id' => $r['segment_id'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $data['lists']);
                \DB::table('campaign_lists_segments')->insert($rows);
            });

            return redirect()
                ->route('manager.campaigns.show', $campaign->uid)
                ->with('success', 'Destinatarios actualizados.');
        }

        return view('campaign::manager.campaigns.recipients', [
            'campaign' => $campaign,
            'mailLists' => CampaignMaillist::with('segments')->orderBy('name')->get(),
            'selected' => $campaign->listsSegments,
        ]);
    }

    /**
     * Confirmar y lanzar la campaña (o programarla con run_at).
     */
    public function confirm(Request $request, string $uid): RedirectResponse
    {
        $campaign = Campaign::where('uid', $uid)->firstOrFail();

        $runAt = $request->input('run_at');
        if ($runAt) {
            $campaign->run_at = Carbon::parse($runAt);
            $campaign->setScheduled();

            return back()->with('success', "Campaña programada para {$campaign->run_at}.");
        }

        if ($this->service->execute($campaign)) {
            return back()->with('success', 'Campaña lanzada. Estado actual: '.$campaign->fresh()->status);
        }

        return back()->with('error', 'No se pudo lanzar la campaña. Revisa los logs.');
    }

    public function pause(string $uid): RedirectResponse
    {
        $campaign = Campaign::where('uid', $uid)->firstOrFail();
        $this->service->pause($campaign);

        return back()->with('success', 'Campaña pausada.');
    }

    public function resume(string $uid): RedirectResponse
    {
        $campaign = Campaign::where('uid', $uid)->firstOrFail();
        $this->service->resume($campaign);

        return back()->with('success', 'Campaña reanudada.');
    }

    /**
     * Vista de log de envíos por estado (sent/failed/bounced/...).
     */
    public function trackingLogs(Request $request, string $uid): View
    {
        $campaign = Campaign::where('uid', $uid)->firstOrFail();

        $logs = $campaign->trackingLogs()
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        return view('campaign::manager.campaigns.tracking_logs', [
            'campaign' => $campaign,
            'logs' => $logs,
        ]);
    }

    protected function statuses(): array
    {
        return [
            BaseCampaign::STATUS_NEW => 'Borrador',
            BaseCampaign::STATUS_SCHEDULED => 'Programada',
            BaseCampaign::STATUS_QUEUING => 'En cola',
            BaseCampaign::STATUS_QUEUED => 'En batch',
            BaseCampaign::STATUS_SENDING => 'Enviando',
            BaseCampaign::STATUS_DONE => 'Completada',
            BaseCampaign::STATUS_PAUSED => 'Pausada',
            BaseCampaign::STATUS_ERROR => 'Error',
        ];
    }
}
