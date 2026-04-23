<?php

namespace Modules\Helpdesk\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendHelpdeskEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<int, string>  $recipients
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        private array $recipients,
        private string $subject,
        private string $view,
        private array $data,
    ) {}

    public function handle(): void
    {
        foreach ($this->recipients as $to) {
            try {
                Mail::send($this->view, $this->data, function ($message) use ($to) {
                    $message->to($to)
                        ->subject($this->subject);
                });
            } catch (\Throwable $e) {
                Log::error('SendHelpdeskEmailJob failed', [
                    'to' => $to,
                    'subject' => $this->subject,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
