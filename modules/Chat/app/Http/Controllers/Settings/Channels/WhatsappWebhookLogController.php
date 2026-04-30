<?php

namespace Modules\Chat\Http\Controllers\Settings\Channels;

use Illuminate\Http\Request;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Models\Channels\Whatsapp;
use Modules\Chat\Models\Channels\WhatsappWebhookLog;

class WhatsappWebhookLogController extends Controller
{
    /**
     * Display webhook logs for a WhatsApp channel
     */
    public function index(Request $request, Whatsapp $whatsapp)
    {
        $this->authorize('view', $whatsapp);

        $query = WhatsappWebhookLog::where('whatsapp_id', $whatsapp->id);

        // Filter by status
        if ($request->get('status') === 'error') {
            $query->withErrors();
        } elseif ($request->get('status') === 'unprocessed') {
            $query->unprocessed();
        }

        // Filter by event type
        if ($eventType = $request->get('event_type')) {
            $query->byEventType($eventType);
        }

        // Get unique event types for dropdown
        $eventTypes = WhatsappWebhookLog::where('whatsapp_id', $whatsapp->id)
            ->distinct()
            ->pluck('event_type')
            ->filter()
            ->values();

        $logs = $query->orderBy('created_at', 'desc')->paginate(50);

        return view('Chat::settings.channels.whatsapps.logs', [
            'whatsapp' => $whatsapp,
            'logs' => $logs,
            'eventTypes' => $eventTypes,
        ]);
    }

    /**
     * Show detailed view of a webhook log
     */
    public function show(Request $request, Whatsapp $whatsapp, WhatsappWebhookLog $log)
    {
        $this->authorize('view', $whatsapp);

        if ($log->whatsapp_id !== $whatsapp->id) {
            abort(404);
        }

        return view('Chat::settings.channels.whatsapps.log-detail', [
            'whatsapp' => $whatsapp,
            'log' => $log,
        ]);
    }

    /**
     * Delete a webhook log
     */
    public function destroy(Request $request, Whatsapp $whatsapp, WhatsappWebhookLog $log)
    {
        $this->authorize('view', $whatsapp);

        if ($log->whatsapp_id !== $whatsapp->id) {
            abort(404);
        }

        $log->delete();

        return redirect()
            ->route('settings.chat.channels.whatsapps.logs', $whatsapp)
            ->with('success', 'Webhook log deleted successfully');
    }

    /**
     * Clear all logs for a WhatsApp channel
     */
    public function clear(Request $request, Whatsapp $whatsapp)
    {
        $this->authorize('view', $whatsapp);

        $deleted = WhatsappWebhookLog::where('whatsapp_id', $whatsapp->id)->delete();

        return redirect()
            ->route('settings.chat.channels.whatsapps.logs', $whatsapp)
            ->with('success', "Deleted $deleted webhook logs");
    }

    /**
     * API endpoint for AJAX refresh
     */
    public function list(Request $request, Whatsapp $whatsapp)
    {
        $this->authorize('view', $whatsapp);

        $query = WhatsappWebhookLog::where('whatsapp_id', $whatsapp->id);

        if ($request->get('status') === 'error') {
            $query->withErrors();
        } elseif ($request->get('status') === 'unprocessed') {
            $query->unprocessed();
        }

        $logs = $query
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get(['id', 'event_type', 'processed', 'error', 'created_at']);

        return response()->json([
            'success' => true,
            'logs' => $logs,
            'total' => WhatsappWebhookLog::where('whatsapp_id', $whatsapp->id)->count(),
        ]);
    }
}
