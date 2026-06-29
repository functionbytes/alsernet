<?php

namespace Modules\HelpdeskEmailLog\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Throwable;

class ResendEmailLogJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public int $backoff = 10;

    public function __construct(public readonly int $emailLogId)
    {
        $this->onQueue('emails');
    }

    public function handle(): void
    {
        $emailLog = EmailLog::query()->find($this->emailLogId);

        if (! $emailLog || empty($emailLog->to_addresses)) {
            return;
        }

        $html = $emailLog->body_html
            ?: ($emailLog->body_text
                ? nl2br(e($emailLog->body_text))
                : '<p>(sin contenido)</p>');

        Mail::html($html, function ($message) use ($emailLog) {
            $message->to($emailLog->to_addresses)->subject($emailLog->subject);

            if ($emailLog->from_address) {
                $message->from($emailLog->from_address, $emailLog->from_name);
            }

            if ($emailLog->cc_addresses) {
                $message->cc($emailLog->cc_addresses);
            }

            if ($emailLog->reply_to) {
                $message->replyTo($emailLog->reply_to);
            }
        });
    }

    public function failed(Throwable $exception): void
    {
        Log::error('ResendEmailLogJob failed', [
            'email_log_id' => $this->emailLogId,
            'error' => $exception->getMessage(),
        ]);
    }
}
