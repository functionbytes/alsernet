<?php

namespace Modules\HelpdeskEmailLog\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskEmailLog\Enums\EmailStatus;
use Modules\HelpdeskEmailLog\Listeners\Concerns\InspectsMailMessage;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Symfony\Component\Mime\Email;
use Throwable;

/**
 * Transitions the EmailLog row created by {@see LogEmailQueued} to "sent".
 *
 * Correlation is done via the Message-ID header (which LogEmailQueued assigns).
 * If no matching queued row is found — e.g. the message was sent without going
 * through MessageSending, or the row creation failed — a fresh "sent" row is
 * created so the email is still recorded.
 *
 * Runs on the queue so the correlation/persistence work never blocks the
 * request that triggered the send. The MessageSent payload (a serialized
 * Symfony Email) carries everything this listener needs, so no message state
 * is lost between dispatch and execution. Tracking must never break delivery,
 * so every failure here is swallowed.
 */
class LogEmailSent implements ShouldQueue
{
    use InspectsMailMessage;
    use InteractsWithQueue;

    public string $queue = 'emails';

    public int $tries = 3;

    public int $backoff = 10;

    public function handle(MessageSent $event): void
    {
        if (! helpdesk_emaillog_enabled()) {
            return;
        }

        try {
            /** @var Email $message */
            $message = $event->message;

            $messageId = $this->messageIdOf($message);
            $log = $this->findQueued($message, $messageId);

            if ($log) {
                $log->forceFill([
                    'message_id' => $messageId ?: $log->message_id,
                    'status' => EmailStatus::Sent,
                    'sent_at' => now(),
                ])->save();

                return;
            }

            $context = $this->contextOf($message, $event->data);
            $from = $this->fromAddressOf($message);

            EmailLog::create([
                ...$context,
                ...$this->currentCauser(),
                'message_id' => $messageId,
                'from_address' => $from?->getAddress() ?: config('mail.from.address') ?: 'unknown@localhost',
                'from_name' => $from?->getName() ?: null,
                'to_addresses' => $this->addressesOf($message->getTo()),
                'cc_addresses' => $this->addressesOf($message->getCc()) ?: null,
                'bcc_addresses' => $this->addressesOf($message->getBcc()) ?: null,
                'reply_to' => $this->addressesOf($message->getReplyTo()) ?: null,
                'subject' => (string) ($message->getSubject() ?? ''),
                'body_html' => $this->bodyOf($message->getHtmlBody(), $context),
                'body_text' => $this->bodyOf($message->getTextBody(), $context),
                'attachments' => $this->attachmentsOf($message) ?: null,
                'metadata' => $this->metaOf($message, $context),
                'status' => EmailStatus::Sent,
                'sent_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('HelpdeskEmailLog: failed to record sent email', ['exception' => $e]);
        }
    }

    public function failed(MessageSent $event, Throwable $exception): void
    {
        Log::error('HelpdeskEmailLog: LogEmailSent listener failed', [
            'error' => $exception->getMessage(),
        ]);
    }

    private function findQueued(Email $message, ?string $messageId): ?EmailLog
    {
        if ($messageId) {
            $byId = EmailLog::queued()->where('message_id', $messageId)->latest('id')->first();

            if ($byId) {
                return $byId;
            }
        }

        $recipients = $this->addressesOf($message->getTo());

        if ($recipients === []) {
            return null;
        }

        // Fallback heuristic when the Message-ID header is unavailable: match
        // subject + ALL recipients within the last 5 min, restricted to rows
        // that never got a message_id assigned. Only transition if exactly one
        // candidate exists to avoid marking the wrong row.
        //
        // Known limitation: the email_logs schema stores no other send-side
        // discriminator (mailer/transport are not persisted), so two identical
        // sends (same subject + recipients) queued within the window are
        // ambiguous on purpose — both rows stay "queued" rather than risking a
        // wrong "sent" transition, and the stale-queued pruning handles them.
        $query = EmailLog::queued()
            ->where('subject', (string) ($message->getSubject() ?? ''))
            ->where('created_at', '>=', now()->subMinutes(5))
            ->whereNull('message_id');

        foreach ($recipients as $address) {
            $query->whereJsonContains('to_addresses', $address);
        }

        $candidates = $query->latest('id')->get();

        return $candidates->count() === 1 ? $candidates->first() : null;
    }
}
