<?php

namespace Modules\Campaign\Http\Controllers\Managers\Campaigns;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Campaign\Library\BaseCampaign;
use Modules\Campaign\Models\Campaign;
use Modules\Campaign\Models\CampaignMaillist;
use Modules\Campaign\Services\CampaignService;
use Modules\CampaignSendingServers\Models\SendingServer;

/**
 * CampaignProController — UI de campañas portada de acellemail (refactor) con
 * el asistente multi-paso. Montado en `campaigns-pro` para CONVIVIR con las
 * campañas existentes del módulo (`manager.campaigns.*`), reutilizando el mismo
 * modelo Campaign + pipeline de envío.
 *
 * Increment 1: listado + selector de tipo + creación de borrador.
 * Increment 2: asistente completo (recipients/setup/schedule/confirm) + overview
 *              + pause/resume/delete/preview.
 */
class CampaignProController extends Controller
{
    public function __construct(private readonly CampaignService $campaignService) {}

    // =========================================================================
    // Listado / índice
    // =========================================================================

    public function index(Request $request)
    {
        $stats = [
            'total' => Campaign::count(),
            'sending' => Campaign::where('status', BaseCampaign::STATUS_SENDING)->count(),
            'done' => Campaign::where('status', BaseCampaign::STATUS_DONE)->count(),
            'draft' => Campaign::whereIn('status', [BaseCampaign::STATUS_NEW, BaseCampaign::STATUS_PAUSED])->count(),
        ];

        return view('campaign::manager.campaigns-pro.index', compact('stats'));
    }

    public function listing(Request $request)
    {
        $query = Campaign::query();

        if ($request->keyword) {
            $query->where('title', 'like', '%'.trim($request->keyword).'%')
                ->orWhere('name', 'like', '%'.trim($request->keyword).'%');
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $campaigns = $query
            ->orderBy($request->sort_order ?? 'created_at', $request->sort_direction ?? 'desc')
            ->paginate($request->per_page ?? 25)
            ->withQueryString();

        return view('campaign::manager.campaigns-pro._list', compact('campaigns'));
    }

    // =========================================================================
    // Paso 0: selector de tipo
    // =========================================================================

    /** Paso 0 del asistente: elegir tipo de campaña. */
    public function selectType(Request $request)
    {
        return view('campaign::manager.campaigns-pro.select_type');
    }

    // =========================================================================
    // Crear borrador
    // =========================================================================

    /**
     * Crear borrador y entrar al asistente (paso 1: recipients).
     */
    public function create(Request $request): RedirectResponse
    {
        $type = in_array($request->type, [Campaign::TYPE_REGULAR, Campaign::TYPE_PLAIN_TEXT], true)
            ? $request->type
            : Campaign::TYPE_REGULAR;

        $campaign = new Campaign;
        $campaign->name = trans('campaign::campaigns.untitled');
        $campaign->title = $campaign->name;
        $campaign->type = $type;
        $campaign->status = BaseCampaign::STATUS_NEW;
        $campaign->save();

        return redirect()->route('manager.campaigns_pro.recipients', $campaign->uid);
    }

    // =========================================================================
    // Paso 1: Recipients
    // =========================================================================

    public function recipients(Request $request, string $uid)
    {
        $campaign = Campaign::where('uid', $uid)->firstOrFail();
        $lists = CampaignMaillist::orderBy('name')->get();
        $step = 1;

        if ($request->isMethod('POST')) {
            $request->validate([
                'list_uids' => ['required', 'array', 'min:1'],
                'list_uids.*' => ['string'],
            ]);

            // Resolver IDs a partir de UIDs
            $selectedIds = CampaignMaillist::whereIn('uid', $request->list_uids)->pluck('id')->toArray();

            // Sincronizar la relación campaign_lists_segments (pivot: campaign_id / mail_list_id)
            DB::table('campaign_lists_segments')
                ->where('campaign_id', $campaign->id)->delete();
            foreach ($selectedIds as $listId) {
                DB::table('campaign_lists_segments')->insert([
                    'campaign_id' => $campaign->id,
                    'mail_list_id' => $listId,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            // Establecer default_maillist_id al primero si no está seteado
            if (! $campaign->default_maillist_id && ! empty($selectedIds)) {
                $campaign->default_maillist_id = $selectedIds[0];
                $campaign->save();
            }

            return redirect()->route('manager.campaigns_pro.setup', $uid);
        }

        // IDs actuales para pre-marcar checkboxes (pivot: mail_list_id)
        $selectedUids = DB::table('campaign_lists_segments')
            ->where('campaign_id', $campaign->id)
            ->join('campaign_maillists', 'campaign_maillists.id', '=', 'campaign_lists_segments.mail_list_id')
            ->pluck('campaign_maillists.uid')->toArray();

        return view('campaign::manager.campaigns-pro.recipients', compact('campaign', 'lists', 'step', 'selectedUids'));
    }

    // =========================================================================
    // Paso 2: Setup
    // =========================================================================

    public function setup(Request $request, string $uid)
    {
        $campaign = Campaign::where('uid', $uid)->firstOrFail();
        $sendingServers = [];
        try {
            $sendingServers = SendingServer::where('status', SendingServer::STATUS_ACTIVE)->get();
        } catch (\Throwable $e) {
            $sendingServers = collect();
        }
        $step = 2;

        if ($request->isMethod('POST')) {
            $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'subject' => ['required', 'string', 'max:998'],
                'from_email' => ['required', 'email', 'max:255'],
                'from_name' => ['required', 'string', 'max:255'],
                'reply_to' => ['nullable', 'email', 'max:255'],
            ]);

            $this->campaignService->update($campaign, [
                'title' => $request->title,
                'subject' => $request->subject,
                'from_email' => $request->from_email,
                'from_name' => $request->from_name,
                'reply_to' => $request->reply_to,
                'track_open' => $request->boolean('track_open') ? 1 : 0,
                'track_click' => $request->boolean('track_click') ? 1 : 0,
                'sign_dkim' => $request->boolean('sign_dkim') ? 1 : 0,
                'preheader' => $request->preheader,
            ]);

            return redirect()->route('manager.campaigns_pro.schedule', $uid);
        }

        return view('campaign::manager.campaigns-pro.setup', compact('campaign', 'sendingServers', 'step'));
    }

    // =========================================================================
    // Paso 3: Schedule
    // =========================================================================

    public function schedule(Request $request, string $uid)
    {
        $campaign = Campaign::where('uid', $uid)->firstOrFail();
        $step = 3;

        if ($request->isMethod('POST')) {
            $sendOption = $request->input('send_option', 'now');

            if ($sendOption === 'scheduled') {
                $request->validate([
                    'run_at' => ['required', 'date', 'after:now'],
                ]);
                $runAt = Carbon::parse($request->run_at);
                $campaign->run_at = $runAt;
                $campaign->status = BaseCampaign::STATUS_SCHEDULED;
            } else {
                $campaign->run_at = null;
                // No cambiar el status aquí — lo hace confirm
            }

            $campaign->save();

            return redirect()->route('manager.campaigns_pro.confirm', $uid);
        }

        return view('campaign::manager.campaigns-pro.schedule', compact('campaign', 'step'));
    }

    // =========================================================================
    // Paso 4: Confirm
    // =========================================================================

    public function confirm(Request $request, string $uid)
    {
        $campaign = Campaign::where('uid', $uid)->firstOrFail();
        $step = 4;

        // Métricas seguras para el resumen
        $subscribersCount = 0;
        try {
            $subscribersCount = $campaign->subscribersCount();
        } catch (\Throwable $e) {
            // fallback 0
        }

        if ($request->isMethod('POST')) {
            // Si ya está programada (run_at futuro) usamos setScheduled vía execute
            // que detecta el run_at y no envía aún.
            $success = $this->campaignService->execute($campaign);

            if ($success) {
                return redirect()
                    ->route('manager.campaigns_pro.index')
                    ->with('success', trans('campaign::campaigns.wizard.send_now'));
            }

            return redirect()
                ->back()
                ->with('error', trans('campaign::campaigns.confirm.no_recipients'));
        }

        return view('campaign::manager.campaigns-pro.confirm', compact('campaign', 'step', 'subscribersCount'));
    }

    // =========================================================================
    // Overview
    // =========================================================================

    public function overview(Request $request, string $uid)
    {
        $campaign = Campaign::where('uid', $uid)->firstOrFail();

        $deliveredCount = 0;
        $openRate = 0.0;
        $clickRate = 0.0;
        $bounceCount = 0;
        $subscribersCount = 0;

        try {
            $deliveredCount = $campaign->deliveredCount();
        } catch (\Throwable $e) {
        }
        try {
            $openRate = $campaign->openRate();
        } catch (\Throwable $e) {
        }
        try {
            $clickRate = $campaign->clickRate();
        } catch (\Throwable $e) {
        }
        try {
            $bounceCount = $campaign->bounceCount();
        } catch (\Throwable $e) {
        }
        try {
            $subscribersCount = $campaign->subscribersCount();
        } catch (\Throwable $e) {
        }

        $lists = [];
        try {
            $lists = $campaign->mailLists()->get();
        } catch (\Throwable $e) {
        }

        return view('campaign::manager.campaigns-pro.overview', compact(
            'campaign',
            'deliveredCount',
            'openRate',
            'clickRate',
            'bounceCount',
            'subscribersCount',
            'lists',
        ));
    }

    // =========================================================================
    // Pause / Resume
    // =========================================================================

    public function pause(Request $request, string $uid): JsonResponse
    {
        $campaign = Campaign::where('uid', $uid)->firstOrFail();
        $success = $this->campaignService->pause($campaign);

        return response()->json([
            'success' => $success,
            'status' => $campaign->fresh()->status,
        ]);
    }

    public function resume(Request $request, string $uid): JsonResponse
    {
        $campaign = Campaign::where('uid', $uid)->firstOrFail();
        $success = $this->campaignService->resume($campaign);

        return response()->json([
            'success' => $success,
            'status' => $campaign->fresh()->status,
        ]);
    }

    // =========================================================================
    // Bulk delete
    // =========================================================================

    public function deleteCampaign(Request $request): JsonResponse
    {
        $uids = $request->input('uids', []);

        if (empty($uids) || ! is_array($uids)) {
            return response()->json(['success' => false, 'message' => 'No UIDs provided'], 422);
        }

        $deleted = 0;
        foreach ($uids as $uid) {
            $campaign = Campaign::where('uid', $uid)->first();
            if ($campaign) {
                $this->campaignService->delete($campaign);
                $deleted++;
            }
        }

        return response()->json(['success' => true, 'deleted' => $deleted]);
    }

    // =========================================================================
    // Preview
    // =========================================================================

    public function preview(Request $request, string $uid)
    {
        $campaign = Campaign::where('uid', $uid)->firstOrFail();

        $html = '';
        try {
            if ($campaign->template && method_exists($campaign->template, 'getPreviewContent')) {
                $html = $campaign->template->getPreviewContent();
            }
        } catch (\Throwable $e) {
            $html = '<p style="font-family:sans-serif;padding:2rem;color:#666">Preview not available.</p>';
        }

        if (empty($html)) {
            $html = '<p style="font-family:sans-serif;padding:2rem;color:#666">No template selected — preview not available.</p>';
        }

        return response($html, 200)->header('Content-Type', 'text/html');
    }
}
