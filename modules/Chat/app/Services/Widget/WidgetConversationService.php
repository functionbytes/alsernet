<?php

namespace Modules\Chat\Services\Widget;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Chat\Events\NewMessageEvent;
use Modules\Chat\Mail\CsatSurveyMail;
use Modules\Chat\Models\Channels\Web;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Conversations\ConversationMessage;
use Modules\Chat\Models\Conversations\ConversationStatus;
use Modules\Chat\Models\CsatSurvey;
use Modules\Chat\Models\Customers\Customer;
use Modules\Chat\Models\Customers\CustomerInbox;
use Modules\Chat\Services\Customers\CustomerLookupService;

class WidgetConversationService
{
    public function __construct(
        private readonly CustomerLookupService $customerLookup
    ) {}

    /**
     * Create a new conversation from widget.
     */
    public function createConversation(string $websiteToken, array $data): array
    {
        $webWidget = Web::where('website_token', $websiteToken)->first();

        if (! $webWidget) {
            throw new \Exception('Invalid widget token');
        }

        $inbox = $webWidget->inbox;

        if (! $inbox) {
            throw new \Exception('Widget not configured');
        }

        DB::beginTransaction();

        try {
            $customer = $this->customerLookup->findOrCreate($webWidget->account_id, $data);

            $customerInbox = CustomerInbox::firstOrCreate([
                'customer_id' => $customer->id,
                'inbox_id' => $inbox->id,
            ], [
                'source_id' => $customer->id,
            ]);

            $openStatus = ConversationStatus::where('slug', 'open')->first();
            $conversation = Conversation::create([
                'account_id' => $webWidget->account_id,
                'inbox_id' => $inbox->id,
                'customer_id' => $customer->id,
                'customer_inbox_id' => $customerInbox->id,
                'status_id' => $openStatus?->id,
                'language' => $data['language'] ?? 'en',
                'last_activity_at' => now(),
            ]);

            if (! empty($data['message'])) {
                $message = ConversationMessage::create([
                    'conversation_id' => $conversation->id,
                    'inbox_id' => $inbox->id,
                    'account_id' => $webWidget->account_id,
                    'sender_id' => $customer->id,
                    'sender_type' => Customer::class,
                    'message_type' => 'incoming',
                    'content' => $data['message'],
                    'content_type' => 'text',
                ]);

                $conversation->update(['last_message_at' => now()]);

                event(new NewMessageEvent($message));
            }

            DB::commit();

            return [
                'conversation_id' => $conversation->id,
                'customer_id' => $customer->id,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get conversation details.
     */
    public function getConversation(int $conversationId, int $customerId): array
    {
        $conversation = Conversation::with(['customer', 'assignee', 'inbox'])->find($conversationId);

        if (! $conversation) {
            throw new \Exception('Conversation not found');
        }

        if ($conversation->customer_id != $customerId) {
            throw new \Exception('Unauthorized access to conversation');
        }

        return [
            'id' => $conversation->id,
            'status' => $conversation->status,
            'created_at' => $conversation->created_at->toIso8601String(),
            'contact' => [
                'id' => $conversation->customer->id,
                'name' => $conversation->customer->name,
                'email' => $conversation->customer->email,
            ],
            'agent' => $conversation->assignee ? [
                'id' => $conversation->assignee->id,
                'name' => $conversation->assignee->name,
            ] : null,
        ];
    }

    /**
     * Send a message in conversation.
     */
    public function sendMessage(int $conversationId, int $customerId, array $data): array
    {
        $conversation = Conversation::find($conversationId);

        if (! $conversation) {
            throw new \Exception('Conversation not found');
        }

        if ($conversation->customer_id != $customerId) {
            throw new \Exception('Unauthorized access to conversation');
        }

        $message = ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'inbox_id' => $conversation->inbox_id,
            'account_id' => $conversation->account_id,
            'sender_id' => $conversation->customer_id,
            'sender_type' => Customer::class,
            'message_type' => 'incoming',
            'content' => $data['content'],
            'content_type' => $data['content_type'] ?? 'text',
        ]);

        $conversation->update([
            'last_activity_at' => now(),
            'last_message_at' => now(),
        ]);

        event(new NewMessageEvent($message));

        return [
            'message_id' => $message->id,
            'created_at' => $message->created_at->toIso8601String(),
        ];
    }

    /**
     * Get conversation messages.
     */
    public function getMessages(int $conversationId, int $customerId): array
    {
        $conversation = Conversation::find($conversationId);

        if (! $conversation) {
            throw new \Exception('Conversation not found');
        }

        if ($conversation->customer_id != $customerId) {
            throw new \Exception('Unauthorized access to conversation');
        }

        $messages = ConversationMessage::where('conversation_id', $conversationId)
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->limit(100)
            ->get()
            ->map(fn ($message) => [
                'id' => $message->id,
                'content' => $message->content,
                'content_type' => $message->content_type,
                'message_type' => $message->message_type,
                'created_at' => $message->created_at->toIso8601String(),
                'sender' => [
                    'type' => class_basename($message->sender_type),
                    'id' => $message->sender_id,
                    'name' => $message->sender->name ?? 'Unknown',
                ],
            ]);

        ConversationMessage::where('conversation_id', $conversationId)
            ->where('message_type', 'outgoing')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return [
            'conversation_id' => $conversationId,
            'messages' => $messages,
        ];
    }

    /**
     * Close conversation from widget.
     */
    public function closeConversation(int $conversationId, int $customerId): void
    {
        $conversation = Conversation::find($conversationId);

        if (! $conversation) {
            throw new \Exception('Conversation not found');
        }

        if ($conversation->customer_id != $customerId) {
            throw new \Exception('Unauthorized access to conversation');
        }

        $closedStatus = ConversationStatus::where('slug', 'closed')->first();
        $conversation->update([
            'status_id' => $closedStatus?->id,
            'closed_at' => now(),
        ]);

        $inbox = $conversation->inbox;
        if ($inbox && $inbox->csat_survey_enabled) {
            $csatSurvey = CsatSurvey::create([
                'account_id' => $conversation->account_id,
                'conversation_id' => $conversation->id,
                'customer_id' => $conversation->customer_id,
            ]);

            $csatSurvey->markAsSent();

            Mail::to($conversation->customer->email)
                ->send(new CsatSurveyMail($csatSurvey));
        }
    }
}
