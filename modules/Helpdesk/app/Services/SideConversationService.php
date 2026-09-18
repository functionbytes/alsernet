<?php

namespace Modules\Helpdesk\Services;

use App\Helpers\PiiMasker;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\SideConversation;
use Modules\Helpdesk\Models\SideConversationMessage;
use Modules\Helpdesk\Notifications\SideConversationCreatedNotification;
use Modules\Helpdesk\Notifications\SideConversationMessageReceivedNotification;

class SideConversationService
{
    /**
     * Create a side conversation with an internal team member.
     */
    public function createWithTeam(
        Conversation $parent,
        int $userId,
        string $subject,
        string $body,
        int $createdBy
    ): SideConversation {
        $participantUser = User::findOrFail($userId);

        $sc = SideConversation::create([
            'parent_conversation_id' => $parent->id,
            'subject' => $subject,
            'participant_type' => 'team',
            'participant_user_id' => $userId,
            'status' => 'open',
            'created_by' => $createdBy,
        ]);

        $this->addMessage($sc, $body, $createdBy);

        try {
            $participantUser->notify(new SideConversationCreatedNotification($sc, $parent));
        } catch (\Throwable $e) {
            Log::warning('SideConversationService: failed to notify participant', [
                'side_conversation_id' => $sc->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $sc;
    }

    /**
     * Create a side conversation with an external email address.
     * Sends an email with a unique reply-to so replies are threaded back.
     */
    public function createWithExternal(
        Conversation $parent,
        string $email,
        string $subject,
        string $body,
        int $createdBy
    ): SideConversation {
        $token = Str::random(32);

        $sc = SideConversation::create([
            'parent_conversation_id' => $parent->id,
            'subject' => $subject,
            'participant_type' => 'external_email',
            'participant_email' => $email,
            'status' => 'open',
            'created_by' => $createdBy,
            'reply_token' => $token,
        ]);

        $this->addMessage($sc, $body, $createdBy);

        try {
            $this->sendExternalEmail($sc, $email, $subject, $body, $token);
        } catch (\Throwable $e) {
            Log::error('SideConversationService: failed to send external email', [
                'side_conversation_id' => $sc->id,
                'email' => PiiMasker::email($email),
                'error' => $e->getMessage(),
            ]);
        }

        return $sc;
    }

    /**
     * Add a message to an existing side conversation.
     */
    public function addMessage(
        SideConversation $sc,
        string $body,
        ?int $userId = null,
        array $attachments = []
    ): SideConversationMessage {
        $isExternal = $userId === null;

        $message = SideConversationMessage::create([
            'side_conversation_id' => $sc->id,
            'author_type' => $isExternal ? 'external' : 'agent',
            'author_id' => $userId,
            'body' => $body,
            'attachments' => $attachments ?: null,
            'is_read' => ! $isExternal,
        ]);

        if ($isExternal) {
            $this->notifyCreatorOfExternalReply($sc);
        }

        return $message;
    }

    /**
     * Handle an incoming email reply matched by reply_token.
     * Called by the IMAP inbound handler.
     */
    public function handleIncomingReply(string $replyToken, string $fromEmail, string $body): bool
    {
        $sc = SideConversation::where('reply_token', $replyToken)->first();

        if (! $sc) {
            return false;
        }

        $message = SideConversationMessage::create([
            'side_conversation_id' => $sc->id,
            'author_type' => 'external',
            'author_id' => null,
            'from_email' => $fromEmail,
            'body' => $body,
            'is_read' => false,
        ]);

        $this->notifyCreatorOfExternalReply($sc);

        return true;
    }

    private function sendExternalEmail(
        SideConversation $sc,
        string $toEmail,
        string $subject,
        string $body,
        string $token
    ): void {
        $replyToAddress = $this->buildReplyToAddress($token);

        Mail::raw($body, function ($message) use ($toEmail, $subject, $replyToAddress) {
            $message->to($toEmail)
                ->subject($subject)
                ->replyTo($replyToAddress);
        });
    }

    private function notifyCreatorOfExternalReply(SideConversation $sc): void
    {
        $creator = User::find($sc->created_by);

        if (! $creator) {
            return;
        }

        try {
            $creator->notify(new SideConversationMessageReceivedNotification($sc));
        } catch (\Throwable $e) {
            Log::warning('SideConversationService: failed to notify creator', [
                'side_conversation_id' => $sc->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function buildReplyToAddress(string $token): string
    {
        $domain = config('helpdesk.side_conversation_domain', config('mail.from.address'));
        $localPart = 'side+'.$token;

        // If domain is a full email, extract the domain part
        if (str_contains($domain, '@')) {
            $domain = substr(strrchr($domain, '@'), 1);
        }

        return $localPart.'@'.$domain;
    }
}
