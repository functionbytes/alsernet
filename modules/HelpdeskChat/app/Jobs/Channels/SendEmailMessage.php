<?php

namespace Modules\HelpdeskChat\Jobs\Channels;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\HelpdeskChat\Models\Channels\Email;
use Modules\HelpdeskChat\Models\Conversations\ConversationMessage;

class SendEmailMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ConversationMessage $message,
        public Email $email,
        public string $recipientEmail,
        public ?string $inReplyTo = null
    ) {
        $this->onQueue('messages');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            if (! $this->email->smtp_enabled) {
                throw new \Exception('SMTP is not enabled for this email channel');
            }

            // Configure SMTP transport
            config([
                'mail.mailers.smtp.host' => $this->email->smtp_address,
                'mail.mailers.smtp.port' => $this->email->smtp_port,
                'mail.mailers.smtp.username' => $this->email->smtp_login,
                'mail.mailers.smtp.password' => $this->email->smtp_password,
                'mail.mailers.smtp.encryption' => $this->getEncryption(),
                'mail.mailers.smtp.verify_peer' => $this->email->smtp_openssl_verify_mode !== 'none',
            ]);

            // Extract conversation subject or use default
            $conversation = $this->message->conversation;
            $subject = $conversation->custom_attributes['subject'] ?? "Re: Conversation #{$conversation->id}";

            // Send email
            Mail::mailer('smtp')->send([], [], function ($message) use ($subject) {
                $message->from($this->email->email)
                    ->to($this->recipientEmail)
                    ->subject($subject)
                    ->html($this->message->content);

                // Add In-Reply-To header for threading
                if ($this->inReplyTo) {
                    $message->getHeaders()->addTextHeader('In-Reply-To', $this->inReplyTo);
                    $message->getHeaders()->addTextHeader('References', $this->inReplyTo);
                }

                // Add ConversationMessage-ID for future threading
                $messageId = '<'.$this->message->id.'@'.$this->email->email.'>';
                $message->getHeaders()->addTextHeader('ConversationMessage-ID', $messageId);
            });

            $this->message->update([
                'status' => 'sent',
            ]);

            Log::info('Email message sent successfully', [
                'message_id' => $this->message->id,
                'recipient' => $this->recipientEmail,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send email message', [
                'message_id' => $this->message->id,
                'error' => $e->getMessage(),
            ]);

            $this->message->update(['status' => 'failed']);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->message->update(['status' => 'failed']);
    }

    /**
     * Get the encryption type for SMTP.
     */
    private function getEncryption(): ?string
    {
        if ($this->email->smtp_enable_ssl_tls) {
            return 'ssl';
        }

        if ($this->email->smtp_enable_starttls_auto) {
            return 'tls';
        }

        return null;
    }
}
