<?php

namespace Modules\Campaign\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\StreamedResponse;
use Modules\Campaign\Models\Campaign;

class CampaignExportController extends Controller
{
    /**
     * Exportar métricas de campaña a CSV.
     */
    public function exportMetrics(string $uid): StreamedResponse
    {
        $campaign = Campaign::where('uid', $uid)->firstOrFail();

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=campaign-{$uid}-metrics.csv",
        ];

        $callback = function () use ($campaign): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Metric', 'Value']);
            fputcsv($handle, ['Campaign', $campaign->name]);
            fputcsv($handle, ['Sent', $campaign->sent_count]);
            fputcsv($handle, ['Opens', $campaign->open_count]);
            fputcsv($handle, ['Clicks', $campaign->click_count]);
            fputcsv($handle, ['Bounces', $campaign->bounce_count]);
            fputcsv($handle, ['Unsubscribes', $campaign->unsubscribe_count]);
            fputcsv($handle, ['Feedback', $campaign->feedback_count]);
            fputcsv($handle, ['Failed', $campaign->failed_count]);
            fputcsv($handle, ['Open Rate %', $campaign->openRate(false)]);
            fputcsv($handle, ['Click Rate %', $campaign->clickRate(false)]);
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Exportar tracking logs a CSV (paginado).
     */
    public function exportLogs(string $uid): StreamedResponse
    {
        $campaign = Campaign::where('uid', $uid)->firstOrFail();

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=campaign-{$uid}-logs.csv",
        ];

        $callback = function () use ($campaign): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Email', 'Status', 'Message ID', 'Error', 'Sent At']);

            $campaign->trackingLogs()
                ->select('email', 'status', 'message_id', 'error', 'created_at')
                ->chunkById(1000, function ($logs) use ($handle): void {
                    foreach ($logs as $log) {
                        fputcsv($handle, [
                            $log->email,
                            $log->status,
                            $log->message_id,
                            $log->error,
                            $log->created_at,
                        ]);
                    }
                });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Top links más clickeados de la campaña.
     */
    public function topLinks(string $uid): JsonResponse
    {
        $campaign = Campaign::where('uid', $uid)->firstOrFail();

        $links = \Illuminate\Support\Facades\DB::table('campaign_links')
            ->leftJoin('campaign_click_logs', 'campaign_click_logs.campaign_link_id', '=', 'campaign_links.id')
            ->where('campaign_links.campaign_id', $campaign->id)
            ->select('campaign_links.url', 'campaign_links.hash', \DB::raw('COUNT(campaign_click_logs.id) as clicks'))
            ->groupBy('campaign_links.id', 'campaign_links.url', 'campaign_links.hash')
            ->orderByDesc('clicks')
            ->limit(20)
            ->get();

        return response()->json([
            'data' => $links,
        ]);
    }
}
