<?php

namespace Modules\Helpdesk\Services\Email;

use App\Models\User;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\EmailAccount;

class SmtpSendService
{
    /**
     * Send a reply email for a helpdesk conversation.
     *
     * Subject is auto-formatted as "[#{conv->id}] {original_subject}" for reply grouping.
     *
     * @param  array<int, string>  $attachments  Absolute local paths to attach
     */
    public function sendReply(
        EmailAccount $account,
        Conversation $conversation,
        string $bodyHtml,
        int $agentUserId = 0,
        array $attachments = [],
    ): bool {
        $subject = $this->buildSubject($conversation);
        $toEmail = $conversation->customer?->email ?? '';

        if (blank($toEmail)) {
            Log::warning('SmtpSendService: no customer email', ['conversation_id' => $conversation->id]);

            return false;
        }

        $signature = $agentUserId ? $this->getAgentSignature($agentUserId) : null;
        $finalBody = $signature ? $bodyHtml.'<br><br>--<br>'.nl2br(e($signature)) : $bodyHtml;

        $fromName = $account->smtp_from_name ?? $account->name;
        $fromEmail = $account->email;

        try {
            $this->sendViaDynamic($account, function (Message $message) use (
                $toEmail, $subject, $finalBody, $fromName, $fromEmail, $attachments
            ) {
                $message
                    ->from($fromEmail, $fromName)
                    ->to($toEmail)
                    ->subject($subject)
                    ->html($finalBody);

                foreach ($attachments as $path) {
                    if (file_exists($path)) {
                        $message->attach($path);
                    }
                }
            });

            return true;
        } catch (\Throwable $e) {
            Log::error('SmtpSendService: send failed', [
                'account_id' => $account->id,
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function sendViaDynamic(EmailAccount $account, \Closure $callback): void
    {
        $mailerKey = 'helpdesk_dynamic_'.$account->id;

        config([
            "mail.mailers.{$mailerKey}" => [
                'transport' => 'smtp',
                'host' => $account->smtp_host,
                'port' => $account->smtp_port,
                'username' => $account->smtp_username,
                'password' => $account->smtp_password,
                'encryption' => $account->smtp_encryption !== 'none' ? $account->smtp_encryption : null,
                'timeout' => 30,
            ],
        ]);

        Mail::mailer($mailerKey)->send([], [], $callback);
    }

    private function buildSubject(Conversation $conversation): string
    {
        $base = $conversation->subject ?? 'Soporte';

        // Already has tag — keep as-is to preserve threading
        if (str_contains($base, '[#'.$conversation->id.']')) {
            return $base;
        }

        // Strip any existing [#NNN] tag from the base to avoid duplication
        $clean = trim(preg_replace('/\[#\d+\]\s*/i', '', $base));

        return "[#{$conversation->id}] {$clean}";
    }

    private function getAgentSignature(int $userId): ?string
    {
        return User::query()->where('id', $userId)->value('email_signature');
    }
}
