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

    /**
     * @param  ?string  $overrideTo  Optional alternative recipient; when set, the
     *                               email is sent only to this address instead of
     *                               the original recipients (cc/reply-to omitted).
     */
    public function __construct(
        public readonly int $emailLogId,
        public readonly ?string $overrideTo = null,
    ) {
        $this->onQueue('emails');
    }

    public function handle(): void
    {
        $emailLog = EmailLog::query()->find($this->emailLogId);

        $recipients = $this->overrideTo ? [$this->overrideTo] : $emailLog?->to_addresses;

        if (! $emailLog || empty($recipients)) {
            return;
        }

        $html = $emailLog->body_html
            ?: ($emailLog->body_text
                ? nl2br(e($emailLog->body_text))
                : '<p>(sin contenido)</p>');

        Mail::html($html, function ($message) use ($emailLog, $recipients) {
            $message->to($recipients)->subject($emailLog->subject);

            if ($emailLog->from_address) {
                $message->from($emailLog->from_address, $emailLog->from_name);
            }

            // Al reenviar a una dirección alternativa se omiten los CC y el
            // reply-to originales, para no exponer el mensaje a los destinatarios
            // reales ni desviar las respuestas al hilo original.
            if (! $this->overrideTo && $emailLog->cc_addresses) {
                $message->cc($emailLog->cc_addresses);
            }

            if (! $this->overrideTo && $emailLog->reply_to) {
                $message->replyTo($emailLog->reply_to);
            }

            // Atribuye la fila de EmailLog que LogEmailQueued crea para este envío
            // al log original, de modo que el reenvío sea trazable (módulo +
            // entity + external_id 'resend:<id>'). El listener lee estas cabeceras
            // X-* y las elimina antes de que salga el correo.
            $headers = $message->getSymfonyMessage()->getHeaders();
            $headers->addTextHeader('X-Email-Module', 'HelpdeskEmailLog');
            $headers->addTextHeader('X-Entity-Type', EmailLog::class);
            $headers->addTextHeader('X-Entity-Id', (string) $emailLog->id);
            $headers->addTextHeader('X-External-Id', 'resend:'.$emailLog->id);
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
