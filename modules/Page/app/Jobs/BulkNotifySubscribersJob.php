<?php

namespace Modules\Page\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Mailrelay\Entities\Subscriber;
use Modules\Page\Mail\PagePublishedMail;
use Modules\Page\Models\Page;

class BulkNotifySubscribersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public int $backoff = 30;

    public function __construct(
        public readonly Page $page,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        if (! config('page.notifications.notify_subscribers', false)) {
            return;
        }

        if (! $this->page->notify_subscribers) {
            return;
        }

        if (! class_exists(Subscriber::class)) {
            return;
        }

        Subscriber::query()
            ->active()
            ->select(['id', 'email'])
            ->chunkById(50, function ($subscribers) {
                foreach ($subscribers as $subscriber) {
                    Mail::to($subscriber->email)
                        ->queue(new PagePublishedMail($this->page, $subscriber));
                }
            });
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('BulkNotifySubscribersJob failed', [
            'page_id' => $this->page->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
