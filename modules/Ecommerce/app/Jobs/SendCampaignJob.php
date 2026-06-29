<?php

namespace Modules\Ecommerce\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Ecommerce\Models\EmailCampaign;
use Modules\Ecommerce\Models\EmailCampaignRecipient;
use Modules\Ecommerce\Services\CustomerSegmentService;

class SendCampaignJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 600;

    public int $backoff = 10;

    public function __construct(public readonly int $campaignId)
    {
        $this->onQueue('emails');
    }

    public function handle(CustomerSegmentService $segments): void
    {
        $campaign = EmailCampaign::query()->findOrFail($this->campaignId);

        if ($campaign->status === 'sent') {
            return;
        }

        $campaign->update(['status' => 'sending']);

        $recipientsCount = 0;

        $segments->getSegment($campaign->segment)
            ->select('id', 'name', 'email')
            ->chunk(100, function ($customers) use ($campaign, &$recipientsCount) {
                foreach ($customers as $customer) {
                    $recipient = EmailCampaignRecipient::query()->create([
                        'campaign_id' => $campaign->id,
                        'customer_id' => $customer->id,
                        'email' => $customer->email,
                        'token' => Str::random(60),
                        'sent_at' => now(),
                    ]);

                    $trackedContent = $this->injectTracking($campaign->content, $recipient->token);

                    Mail::html($trackedContent, function ($m) use ($campaign, $customer) {
                        $m->to($customer->email)->subject($campaign->subject);
                    });

                    $recipientsCount++;
                }
            });

        $campaign->update([
            'status' => 'sent',
            'sent_at' => now(),
            'recipients_count' => $recipientsCount,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendCampaignJob failed', [
            'campaign_id' => $this->campaignId,
            'error' => $exception->getMessage(),
        ]);

        EmailCampaign::query()->where('id', $this->campaignId)
            ->where('status', 'sending')
            ->update(['status' => 'draft']);
    }

    private function injectTracking(string $html, string $token): string
    {
        $pixel = '<img src="'.url("/api/v1/ecommerce/email-tracking/open/{$token}").'" width="1" height="1" alt="" style="display:none">';

        if (str_contains($html, '</body>')) {
            $html = str_replace('</body>', $pixel.'</body>', $html);
        } else {
            $html .= $pixel;
        }

        return (string) preg_replace_callback('/href="(https?:\/\/[^"]+)"/', function ($matches) use ($token) {
            $url = urlencode($matches[1]);

            return 'href="'.url("/api/v1/ecommerce/email-tracking/click/{$token}?url={$url}").'"';
        }, $html);
    }
}
