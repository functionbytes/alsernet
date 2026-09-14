<?php

namespace Modules\Helpdesk\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendHelpdeskEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public int $backoff = 10;

    /**
     * @param  array<int, string>  $recipients
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        private array $recipients,
        private string $subject,
        private string $view,
        private array $data,
    ) {
        $this->onQueue('emails');
    }

    public function handle(): void
    {
        foreach ($this->recipients as $to) {
            Mail::send($this->view, $this->data, function ($message) use ($to) {
                $message->to($to)->subject($this->subject);
            });
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendHelpdeskEmailJob failed', [
            'recipients' => $this->recipients,
            'subject' => $this->subject,
            'error' => $exception->getMessage(),
        ]);
    }
}
