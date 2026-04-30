<?php

namespace Modules\Campaign\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Campaign\Models\Campaign;

/**
 * Archiva tracking logs de campañas antiguas a tabla de archivo,
 * y elimina los originales para reducir peso de la BD.
 */
class CampaignArchiver
{
    public function archiveCampaign(Campaign $campaign): int
    {
        $moved = 0;
        DB::table('campaign_tracking_logs')
            ->where('campaign_id', $campaign->id)
            ->where('created_at', '<', now()->subDays(30))
            ->chunkById(1000, function ($logs) use (&$moved): void {
                $rows = collect($logs)->map(fn ($log) => [
                    'uid' => $log->uid,
                    'campaign_id' => $log->campaign_id,
                    'subscriber_id' => $log->subscriber_id,
                    'sending_server_id' => $log->sending_server_id,
                    'email' => $log->email,
                    'message_id' => $log->message_id,
                    'runtime_message_id' => $log->runtime_message_id,
                    'status' => $log->status,
                    'error' => $log->error,
                    'created_at' => $log->created_at,
                ])->all();

                DB::table('campaign_tracking_logs_archive')->insert($rows);
                DB::table('campaign_tracking_logs')->whereIn('id', collect($logs)->pluck('id'))->delete();
                $moved += count($rows);
            });

        Log::info('Campaign archived', ['campaign_id' => $campaign->id, 'moved' => $moved]);

        return $moved;
    }

    public function archiveOlderThan(int $days = 365): int
    {
        $total = 0;
        Campaign::where('status', 'done')
            ->where('updated_at', '<', now()->subDays($days))
            ->chunkById(50, function ($campaigns) use (&$total): void {
                foreach ($campaigns as $campaign) {
                    $total += $this->archiveCampaign($campaign);
                }
            });

        return $total;
    }
}
