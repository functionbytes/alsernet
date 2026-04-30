<?php

namespace Modules\Chat\Jobs\Channels;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Events\MessageSent;
use Modules\Chat\Models\Channels\Email;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Conversations\ConversationMessage;
use Modules\Chat\Models\Conversations\ConversationStatus;
use Modules\Chat\Models\Customers\Customer;
use Modules\Chat\Models\Customers\CustomerInbox;

class ProcessInboundEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Email $email,
        public array $emailData
    ) {
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $inbox = $this->email->inbox;

            if (! $inbox) {
                Log::error('Email channel has no inbox', [
                    'email_id' => $this->email->id,
                ]);

                return;
            }

            // Find or create customer
            $customer = $this->findOrCreateCustomer($inbox->account_id);

            // Find or create customer inbox
            $customerInbox = $this->findOrCreateCustomerInbox($customer, $inbox);

            // Find or create conversation (based on threading)
            $conversation = $this->findOrCreateConversation($customer, $inbox, $customerInbox);

            // Create message
            $message = $this->createMessage($conversation, $customer);

            // Broadcast the message
            broadcast(new MessageSent($message->load('sender')))->toOthers();

            Log::info('Inbound email processed successfully', [
                'email_id' => $this->email->id,
                'message_id' => $message->id,
                'conversation_id' => $conversation->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Error processing inbound email', [
                'email_id' => $this->email->id,
                'from' => $this->emailData['from_email'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Find or create customer.
     */
    protected function findOrCreateCustomer(int $accountId): Customer
    {
        $customer = Customer::where('account_id', $accountId)
            ->where('email', $this->emailData['from_email'])
            ->first();

        if (! $customer) {
            $customer = Customer::create([
                'account_id' => $accountId,
                'name' => $this->emailData['from_name'] ?? $this->emailData['from_email'],
                'email' => $this->emailData['from_email'],
            ]);
        }

        return $customer;
    }

    /**
     * Find or create customer inbox.
     */
    protected function findOrCreateCustomerInbox(Customer $customer, $inbox): CustomerInbox
    {
        $customerInbox = CustomerInbox::where('customer_id', $customer->id)
            ->where('inbox_id', $inbox->id)
            ->first();

        if (! $customerInbox) {
            $customerInbox = CustomerInbox::create([
                'customer_id' => $customer->id,
                'inbox_id' => $inbox->id,
                'source_id' => $this->emailData['from_email'], // Store sender email for replies
            ]);
        }

        return $customerInbox;
    }

    /**
     * Find or create conversation.
     */
    protected function findOrCreateConversation(Customer $customer, $inbox, CustomerInbox $customerInbox): Conversation
    {
        // Try to find existing conversation by In-Reply-To or References
        $conversation = null;

        if (! empty($this->emailData['in_reply_to']) || ! empty($this->emailData['references'])) {
            $conversation = $this->findConversationByThreading($inbox->account_id);
        }

        // If no conversation found, create a new one
        if (! $conversation) {
            $openStatus = ConversationStatus::where('slug', 'open')->first();
            $conversation = Conversation::create([
                'account_id' => $inbox->account_id,
                'inbox_id' => $inbox->id,
                'customer_id' => $customer->id,
                'customer_inbox_id' => $customerInbox->id,
                'status_id' => $openStatus?->id,
                'last_activity_at' => now(),
                'custom_attributes' => [
                    'subject' => $this->emailData['subject'],
                    'in_reply_to' => $this->emailData['in_reply_to'] ?? null,
                ],
            ]);
        } else {
            // Update last activity
            $conversation->updateLastActivity();

            // Reopen if resolved
            if ($conversation->isResolved()) {
                $conversation->markAsOpen();
            }
        }

        return $conversation;
    }

    /**
     * Find conversation by email threading headers.
     */
    protected function findConversationByThreading(int $accountId): ?Conversation
    {
        // Try to find by In-Reply-To
        if (! empty($this->emailData['in_reply_to'])) {
            $conversation = Conversation::where('account_id', $accountId)
                ->whereJsonContains('custom_attributes->in_reply_to', $this->emailData['in_reply_to'])
                ->orWhereHas('messages', function ($query) {
                    $query->where('source_id', $this->emailData['in_reply_to']);
                })
                ->first();

            if ($conversation) {
                return $conversation;
            }
        }

        // Try to find by subject (simple threading fallback)
        if (! empty($this->emailData['subject'])) {
            $cleanSubject = preg_replace('/^(Re:|Fwd?:)\s*/i', '', trim($this->emailData['subject']));

            $conversation = Conversation::where('account_id', $accountId)
                ->whereJsonContains('custom_attributes->subject', $cleanSubject)
                ->where('customer_id', $this->findOrCreateCustomer($accountId)->id)
                ->where('created_at', '>', now()->subDays(config('chat.email.threading_lookback_days')))
                ->orderBy('created_at', 'desc')
                ->first();

            if ($conversation) {
                return $conversation;
            }
        }

        return null;
    }

    /**
     * Create message from email.
     */
    protected function createMessage(Conversation $conversation, Customer $customer): ConversationMessage
    {
        return ConversationMessage::create([
            'account_id' => $conversation->account_id,
            'inbox_id' => $conversation->inbox_id,
            'conversation_id' => $conversation->id,
            'sender_id' => $customer->id,
            'sender_type' => Customer::class,
            'message_type' => 'incoming',
            'content_type' => $this->emailData['is_html'] ? 'html' : 'text',
            'content' => $this->emailData['body'],
            'source_id' => $this->emailData['message_id'] ?? null,
            'status' => 'sent',
            'private' => false,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error(static::class.' failed', [
            'email_id' => $this->email->id,
            'from' => $this->emailData['from_email'] ?? 'unknown',
            'error' => $exception->getMessage(),
        ]);
    }
}
