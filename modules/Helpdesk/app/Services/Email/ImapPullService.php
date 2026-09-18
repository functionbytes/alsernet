<?php

namespace Modules\Helpdesk\Services\Email;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\EmailAccount;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Message;

class ImapPullService
{
    public function pullAll(): int
    {
        $total = 0;

        foreach (EmailAccount::active()->get() as $account) {
            if (! $account->hasImap()) {
                continue;
            }

            try {
                $total += $this->pullAccount($account);
            } catch (\Throwable $e) {
                Log::error('ImapPullService: account pull failed', [
                    'account_id' => $account->id,
                    'email' => $account->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $total;
    }

    public function pullAccount(EmailAccount $account): int
    {
        $cm = new ClientManager;

        $client = $cm->make([
            'host' => $account->imap_host,
            'port' => $account->imap_port,
            'encryption' => $account->imap_encryption,
            'validate_cert' => (bool) ($account->imap_validate_cert ?? true),
            'username' => $account->imap_username,
            'password' => $account->imap_password,
            'protocol' => 'imap',
        ]);

        $client->connect();

        $folder = $client->getFolder($account->imap_folder ?? 'INBOX');
        $query = $folder->query()->unseen();

        if ($account->last_sync_at) {
            $query->since($account->last_sync_at);
        }

        $messages = $query->limit(50)->get();
        $processed = 0;

        foreach ($messages as $message) {
            $this->processMessage($message, $account);
            $message->setFlag('Seen');
            $processed++;
        }

        $client->disconnect();

        $account->update(['last_sync_at' => now()]);

        return $processed;
    }

    private function processMessage(Message $message, EmailAccount $account): void
    {
        $from = $message->getFrom()?->first();
        $fromEmail = $from?->mail ?? '';
        $fromName = $from?->personal ?? $fromEmail;
        $subject = (string) ($message->getSubject()?->first() ?? '(sin asunto)');
        $body = (string) ($message->getTextBody() ?? $message->getHTMLBody() ?? '');
        $messageId = (string) ($message->getMessageId()?->first() ?? '');

        if (blank($fromEmail)) {
            return;
        }

        // Skip duplicate
        if ($messageId && $this->isDuplicate($messageId)) {
            return;
        }

        // Agent reply-by-email: if sender is a system user AND subject has [#NNN]
        $agent = User::query()->where('email', $fromEmail)->first();
        if ($agent && preg_match('/\[#(\d+)\]/i', $subject, $m)) {
            $conversation = Conversation::query()->find((int) $m[1]);
            if ($conversation) {
                $this->addAgentReply($conversation, $agent->id, $body, $messageId);

                return;
            }
        }

        // Customer email (skip if sender is an agent without a matching conv)
        if ($agent) {
            return; // Agent emails without [#NNN] are ignored
        }

        $customer = Customer::query()->firstOrCreate(
            ['email' => $fromEmail],
            ['name' => $fromName],
        );

        $conversation = $this->resolveConversation($subject, $customer->id, $account);
        $this->addCustomerMessage($conversation, $customer->id, $body, $messageId);
        $conversation->update(['last_message_at' => now()]);
    }

    private function resolveConversation(string $subject, int $customerId, EmailAccount $account): Conversation
    {
        // Thread by [#NNN] tag — require the conversation to belong to the same
        // customer to prevent cross-customer message injection via a forged subject.
        if (preg_match('/\[#(\d+)\]/i', $subject, $m)) {
            $existing = Conversation::query()
                ->where('id', (int) $m[1])
                ->where('customer_id', $customerId)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $statusId = ConversationStatus::query()
            ->where('is_default', true)
            ->orWhere('is_open', true)
            ->orderByDesc('is_default')
            ->value('id') ?? 1;

        $conversation = Conversation::create([
            'customer_id' => $customerId,
            'channel' => 'email',
            'subject' => $subject,
            'last_message_at' => now(),
            'inbox_id' => $account->inbox_id,
            'status_id' => $statusId,
        ]);

        return $conversation;
    }

    private function addCustomerMessage(Conversation $conversation, int $customerId, string $body, string $messageId): void
    {
        ConversationItem::create([
            'conversation_id' => $conversation->id,
            'author_id' => $customerId,
            'type' => 'message',
            'body' => $body ?: '[sin contenido]',
            'external_id' => $messageId ?: null,
            'is_internal' => false,
            'metadata' => ['platform' => 'email'],
        ]);
    }

    private function addAgentReply(Conversation $conversation, int $userId, string $body, string $messageId): void
    {
        ConversationItem::create([
            'conversation_id' => $conversation->id,
            'user_id' => $userId,
            'author_id' => null,
            'type' => 'message',
            'body' => $body ?: '[sin contenido]',
            'external_id' => $messageId ?: null,
            'is_internal' => false,
            'metadata' => ['platform' => 'email', 'source' => 'reply-by-email'],
        ]);
        $conversation->update(['last_message_at' => now()]);
    }

    private function isDuplicate(string $messageId): bool
    {
        return ConversationItem::query()
            ->where('external_id', $messageId)
            ->exists();
    }
}
