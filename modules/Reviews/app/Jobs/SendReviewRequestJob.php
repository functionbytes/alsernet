<?php

namespace Modules\Reviews\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Reviews\Mail\ReviewRequestMail;
use Modules\Reviews\Models\ReviewRequestSend;

class SendReviewRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public array $backoff = [30, 60, 120];

    public function __construct(
        public readonly ReviewRequestSend $send,
    ) {
        $this->onQueue('emails');
    }

    public function handle(): void
    {
        $send = $this->send;
        $campaign = $send->campaign;

        Mail::to($send->customer_email)->send(new ReviewRequestMail($campaign, $send));

        $send->update([
            'sent_at' => now(),
            'status' => 'sent',
        ]);

        $campaign->increment('sent_count');
    }

    public function failed(\Throwable $exception): void
    {
        $this->send->update(['status' => 'failed']);

        Log::error('SendReviewRequestJob failed', [
            'send_id' => $this->send->id,
            'campaign_id' => $this->send->campaign_id,
            'email' => $this->send->customer_email,
            'error' => $exception->getMessage(),
        ]);
    }
}
