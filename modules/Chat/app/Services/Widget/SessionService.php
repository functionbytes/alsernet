<?php

namespace Modules\Chat\Services\Widget;

use Illuminate\Support\Facades\DB;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Conversations\ConversationSession;
use Modules\Chat\Models\Conversations\ConversationStatus;
use Modules\Chat\Models\Customers\Customer;
use Modules\Chat\Models\Customers\PrestashopCustomerSync;

class SessionService
{
    /**
     * Initialize or retrieve chat session.
     */
    public function initializeSession(string $token, Account $account): ConversationSession
    {
        return ConversationSession::firstOrCreate(
            [
                'token' => $token,
                'account_id' => $account->id,
            ],
            [
                'session_id' => session()->getId(),
                'last_activity_at' => now(),
                'active' => true,
            ]
        );
    }

    /**
     * Sync session with PrestaShop customer.
     */
    public function syncWithPrestashop(
        ConversationSession $session,
        int $prestashopCustomerId,
        string $email,
        string $name
    ): Customer {
        DB::beginTransaction();

        try {
            // Check if sync record exists
            $sync = PrestashopCustomerSync::where('account_id', $session->account_id)
                ->where('prestashop_customer_id', $prestashopCustomerId)
                ->first();

            if ($sync) {
                // Update existing sync
                $customer = $sync->chatCustomer;
                $sync->update([
                    'email' => $email,
                    'last_sync_at' => now(),
                ]);
            } else {
                // Create new customer and sync
                $customer = Customer::create([
                    'account_id' => $session->account_id,
                    'name' => $name,
                    'email' => $email,
                ]);

                PrestashopCustomerSync::create([
                    'account_id' => $session->account_id,
                    'prestashop_customer_id' => $prestashopCustomerId,
                    'customer_id' => $customer->id,
                    'email' => $email,
                    'last_sync_at' => now(),
                ]);
            }

            // Link session to customer
            $session->update([
                'prestashop_customer_id' => $prestashopCustomerId,
                'customer_id' => $customer->id,
            ]);

            DB::commit();

            return $customer;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create or retrieve customer for anonymous session.
     */
    public function getOrCreateAnonymousCustomer(ConversationSession $session, ?string $email = null, ?string $name = null): Customer
    {
        if ($session->customer_id) {
            return $session->chatCustomer;
        }

        $customer = Customer::create([
            'account_id' => $session->account_id,
            'name' => $name ?? 'Anonymous',
            'email' => $email ?? 'anonymous_'.time().'@chat.local',
            'identifier' => $session->token,
        ]);

        $session->update(['customer_id' => $customer->id]);

        return $customer;
    }

    /**
     * Get active conversation for session or create new one.
     */
    public function getOrCreateConversation(ConversationSession $session, Customer $customer): Conversation
    {
        // Try to find active conversation for this session
        $conversation = Conversation::where('session_id', $session->id)
            ->whereHas('status', fn ($q) => $q->whereIn('slug', ['open', 'pending']))
            ->latest()
            ->first();

        if ($conversation) {
            return $conversation;
        }

        // Create new conversation
        $openStatus = ConversationStatus::where('slug', 'open')->first();

        return Conversation::create([
            'account_id' => $session->account_id,
            'customer_id' => $customer->id,
            'session_id' => $session->id,
            'status_id' => $openStatus?->id,
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Load conversation for session.
     */
    public function loadConversations(ConversationSession $session): array
    {
        if (! $session->customer_id) {
            return [];
        }

        $conversations = Conversation::where('session_id', $session->id)
            ->with(['messages' => function ($query) {
                $query->where('private', false)
                    ->latest()
                    ->limit(50);
            }])
            ->latest()
            ->limit(10)
            ->get();

        return $conversations->map(function ($conversation) {
            return [
                'id' => $conversation->id,
                'status' => $conversation->status,
                'created_at' => $conversation->created_at->toIso8601String(),
                'messages' => $conversation->messages->map(function ($message) {
                    return [
                        'id' => $message->id,
                        'content' => $message->content,
                        'message_type' => $message->message_type,
                        'created_at' => $message->created_at->toIso8601String(),
                    ];
                })->toArray(),
            ];
        })->toArray();
    }
}
