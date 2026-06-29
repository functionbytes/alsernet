<?php

namespace Modules\Campaign\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\StreamedResponse;
use Modules\Campaign\Models\CampaignMaillist;
use Modules\Campaign\Models\CampaignSubscriber;

class SubscriberExportController extends Controller
{
    public function export(string $listUid): StreamedResponse
    {
        $list = CampaignMaillist::where('uid', $listUid)->firstOrFail();

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename= subscribers-{$listUid}.csv",
        ];

        $callback = function () use ($list): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Email', 'First Name', 'Last Name', 'Status', 'Subscribed At', 'Source']);

            CampaignSubscriber::query()
                ->select('campaign_subscribers.email', 'campaign_subscribers.first_name', 'campaign_subscribers.last_name', 'campaign_subscribers.source', 'cms.status', 'cms.subscribed_at')
                ->join('campaign_maillists_subscribers as cms', 'cms.subscriber_id', '=', 'campaign_subscribers.id')
                ->where('cms.mail_list_id', $list->id)
                ->orderBy('campaign_subscribers.email')
                ->chunkById(1000, function ($rows) use ($handle): void {
                    foreach ($rows as $row) {
                        fputcsv($handle, [
                            $row->email,
                            $row->first_name,
                            $row->last_name,
                            $row->status,
                            $row->subscribed_at,
                            $row->source,
                        ]);
                    }
                });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
