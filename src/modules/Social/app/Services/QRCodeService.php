<?php

namespace Modules\Social\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Social\Models\Campaign;
use Modules\Social\Models\CampaignQrCode;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QRCodeService
{
    public function generateForCampaign(Campaign $campaign, ?string $customUrl = null): CampaignQrCode
    {
        $code = Str::random(10);
        $url = $customUrl ?? route('public.campaign.qr', ['code' => $code]);

        // Generate QR Code
        $qrCode = QrCode::format('png')
            ->size(500)
            ->margin(2)
            ->errorCorrection('H')
            ->generate($url);

        // Store QR Code image
        $fileName = "qr-codes/campaign-{$campaign->id}-{$code}.png";
        Storage::disk('public')->put($fileName, $qrCode);

        return CampaignQrCode::create([
            'campaign_id' => $campaign->id,
            'code' => $code,
            'url' => $url,
            'file_path' => $fileName,
        ]);
    }

    public function trackScan(CampaignQrCode $qrCode, array $metadata = []): void
    {
        $qrCode->increment('scans_count');
        $qrCode->update([
            'last_scanned_at' => now(),
            'scan_data' => array_merge($qrCode->scan_data ?? [], [
                [
                    'timestamp' => now()->toIso8601String(),
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'metadata' => $metadata,
                ],
            ]),
        ]);
    }

    public function getAnalytics(CampaignQrCode $qrCode): array
    {
        $scanData = $qrCode->scan_data ?? [];

        return [
            'total_scans' => $qrCode->scans_count,
            'unique_ips' => count(array_unique(array_column($scanData, 'ip'))),
            'last_scan' => $qrCode->last_scanned_at,
            'scans_by_day' => $this->getScansByDay($scanData),
            'scans_by_hour' => $this->getScansByHour($scanData),
        ];
    }

    protected function getScansByDay(array $scanData): array
    {
        $byDay = [];

        foreach ($scanData as $scan) {
            $day = date('Y-m-d', strtotime($scan['timestamp']));
            $byDay[$day] = ($byDay[$day] ?? 0) + 1;
        }

        return $byDay;
    }

    protected function getScansByHour(array $scanData): array
    {
        $byHour = [];

        foreach ($scanData as $scan) {
            $hour = (int) date('H', strtotime($scan['timestamp']));
            $byHour[$hour] = ($byHour[$hour] ?? 0) + 1;
        }

        return $byHour;
    }
}
