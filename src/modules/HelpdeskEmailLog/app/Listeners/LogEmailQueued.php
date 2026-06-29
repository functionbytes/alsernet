<?php

namespace Modules\HelpdeskEmailLog\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\HelpdeskEmailLog\Enums\EmailStatus;
use Modules\HelpdeskEmailLog\Listeners\Concerns\InspectsMailMessage;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Symfony\Component\Mime\Email;
use Throwable;

/**
 * Records an email as "queued" right before it is handed to the transport.
 *
 * Responsibilities:
 *  - Create the EmailLog row (status = queued).
 *  - Assign a Message-ID so {@see LogEmailSent} can correlate the MessageSent
 *    event back to this row (the transport may clone the message, so object
 *    identity cannot be relied upon).
 *  - Strip the internal X-* tracking headers so they never reach the recipient.
 *
 * Tracking must never break delivery, so every failure here is swallowed.
 */
class LogEmailQueued
{
    use InspectsMailMessage;

    public function handle(MessageSending $event): void
    {
        /** @var Email $message */
        $message = $event->message;

        // Read context/headers and strip internal X-* headers BEFORE any DB
        // operation so they can never reach the recipient even if the insert fails.
        $messageId = $this->ensureMessageId($message);
        $context = $this->contextOf($message, $event->data);
        $from = $this->fromAddressOf($message);
        $this->stripInternalHeaders($message);

        try {
            EmailLog::create([
                ...$context,
                ...$this->currentCauser(),
                'from_address' => $from?->getAddress() ?: config('mail.from.address') ?: 'unknown@localhost',
                'from_name' => $from?->getName() ?: null,
                'to_addresses' => $this->addressesOf($message->getTo()),
                'cc_addresses' => $this->addressesOf($message->getCc()) ?: null,
                'bcc_addresses' => $this->addressesOf($message->getBcc()) ?: null,
                'reply_to' => $this->addressesOf($message->getReplyTo()) ?: null,
                'subject' => (string) ($message->getSubject() ?? ''),
                'message_id' => $messageId,
                'body_html' => $this->bodyOf($message->getHtmlBody(), $context),
                'body_text' => $this->bodyOf($message->getTextBody(), $context),
                'attachments' => $this->attachmentsOf($message) ?: null,
                'metadata' => $this->metaOf($message, $context),
                'status' => EmailStatus::Queued,
            ]);
        } catch (Throwable $e) {
            Log::warning('HelpdeskEmailLog: failed to record queued email', ['exception' => $e]);
        }
    }

    private function ensureMessageId(Email $message): string
    {
        $headers = $message->getHeaders();

        if ($headers->has('Message-ID')) {
            return trim($headers->get('Message-ID')->getBodyAsString(), '<>');
        }

        $domain = Str::after(config('mail.from.address') ?: 'localhost', '@');
        $id = Str::orderedUuid()->toString().'@'.($domain ?: 'localhost');

        $headers->addIdHeader('Message-ID', $id);

        return $id;
    }

    private function stripInternalHeaders(Email $message): void
    {
        $headers = $message->getHeaders();

        foreach ((array) config('helpdeskemaillog.internal_headers', []) as $name) {
            if ($headers->has($name)) {
                $headers->remove($name);
            }
        }
    }
}
