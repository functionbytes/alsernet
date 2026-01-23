<?php

namespace Modules\Mailrelay\Http\Controllers\Mailrelay;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Mailrelay\Services\CampaignService;

class CampaignController extends Controller
{
    protected $campaignService;

    public function __construct(CampaignService $campaignService)
    {
        $this->campaignService = $campaignService;
    }

    // Listar todas las campañas
    public function index()
    {
        $campaigns = \App\Models\Campaign::latest()->paginate(25);

        return response()->json([
            'data' => $campaigns->items(),
            'meta' => [
                'current_page' => $campaigns->currentPage(),
                'total' => $campaigns->total(),
                'per_page' => $campaigns->perPage(),
            ],
        ]);
    }

    // Crear una campaña
    public function create(Request $request)
    {
        //        $data = $request->validate([
        //            'name' => 'required|string',
        //            'subject' => 'required|string',
        //            'list_id' => 'required|integer',
        //            'html_content' => 'required|string',
        //            'text_content' => 'required|string',
        //            'sender' => 'required|integer',
        //            'target' => 'required|string',
        //            'html' => 'required|string',
        //            'preview_text' => 'required|string',
        //            'segment_id' => 'required|integer',
        //            'group_ids' => 'required|array',
        //            'campaign_folder_id' => 'required|integer',
        //            'url_token' => 'required|boolean',
        //            'analytics_utm_campaign' => 'required|string',
        //            'use_premailer' => 'required|boolean',
        //            'reply_to' => 'required|string',
        //        ]);
        $data = $request->all();

        return response()->json($this->campaignService->createCampaign($data));
    }

    // Obtener una campaña por ID
    public function get($id)
    {
        return response()->json($this->campaignService->getCampaign($id));
    }

    // Eliminar una campaña
    public function delete($id)
    {
        return response()->json($this->campaignService->deleteCampaign($id));
    }

    // Actualizar una campaña
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
            'subject' => 'sometimes|string',
            'list_id' => 'sometimes|integer',
            'html_content' => 'sometimes|string',
            'text_content' => 'sometimes|string',
        ]);

        return response()->json($this->campaignService->updateCampaign($id, $data));
    }

    // Enviar una campaña a todos
    public function sendAll(Request $request, $id)
    {
        $data = $request->validate([
            'target' => 'required|string',
            'group_ids' => 'required|array',
            'segment_id' => 'required|integer',
            'scheduled_at' => 'required|date_format:Y-m-d H:i:s',
            'callback_url' => 'nullable|url',
        ]);

        return response()->json($this->campaignService->sendAllCampaign($id, $data));
    }

    // Enviar una campaña de prueba
    public function sendTest(Request $request, $id)
    {
        $data = $request->validate([
            'test_emails' => 'required|string',
        ]);

        return response()->json($this->campaignService->sendTestCampaign($id, $data));
    }

    // Enviar campaña
    public function send($id)
    {
        $campaign = \App\Models\Campaign::findOrFail($id);

        if ($campaign->isSent()) {
            return response()->json([
                'error' => 'Campaign already sent',
            ], 422);
        }

        $mailrelay = app(\App\Services\MailRelayService::class);
        $success = $mailrelay->sendCampaign($campaign);

        if (! $success) {
            return response()->json([
                'error' => 'Failed to send campaign',
            ], 500);
        }

        return response()->json([
            'data' => $campaign->fresh(),
            'message' => 'Campaign sent successfully',
        ]);
    }

    // Obtener analytics de campaña
    public function analytics($id)
    {
        $campaign = \App\Models\Campaign::with('analytics')->findOrFail($id);

        $mailrelay = app(\App\Services\MailRelayService::class);
        $mailrelay->syncCampaignAnalytics($campaign);

        $analytics = $campaign->analytics;

        if (! $analytics) {
            return response()->json([
                'data' => null,
                'message' => 'Analytics not available yet',
            ]);
        }

        return response()->json([
            'data' => [
                'campaign_id' => $campaign->id,
                'sent_count' => $analytics->sent_count,
                'opened_count' => $analytics->opened_count,
                'clicked_count' => $analytics->clicked_count,
                'bounced_count' => $analytics->bounced_count,
                'unsubscribed_count' => $analytics->unsubscribed_count,
                'open_rate' => $analytics->open_rate,
                'click_rate' => $analytics->click_rate,
                'last_synced_at' => $analytics->last_synced_at,
            ],
        ]);
    }
}
