<?php

namespace Modules\HelpdeskChat\Services\Widget;

use App\Models\Account;
use App\Models\ChatSession;
use App\Models\PrestashopCustomerSync;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskChat\Models\Conversation;
use Modules\HelpdeskChat\Models\Customer;

class SessionService
{
    /**
     * Initialize or retrieve chat session.
     */
    public function initializeSession(string $token, Account $account): ChatSession
    {
        return ChatSession::firstOrCreate(
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
        ChatSession $session,
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
                $customer = $sync->helpdeskCustomer;
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
                    'helpdesk_customer_id' => $customer->id,
                    'email' => $email,
                    'last_sync_at' => now(),
                ]);
            }

            // Link session to customer
            $session->update([
                'prestashop_customer_id' => $prestashopCustomerId,
                'helpdesk_customer_id' => $customer->id,
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
    public function getOrCreateAnonymousCustomer(ChatSession $session, ?string $email = null, ?string $name = null): Customer
    {
        if ($session->helpdesk_customer_id) {
            return $session->helpdeskCustomer;
        }

        $customer = Customer::create([
            'account_id' => $session->account_id,
            'name' => $name ?? 'Anonymous',
            'email' => $email ?? 'anonymous_'.time().'@helpdesk.local',
            'identifier' => $session->token,
        ]);

        $session->update(['helpdesk_customer_id' => $customer->id]);

        return $customer;
    }

    /**
     * Get active conversation for session or create new one.
     */
    public function getOrCreateConversation(ChatSession $session, Customer $customer): Conversation
    {
        // Try to find active conversation for this session
        $conversation = Conversation::where('chat_session_id', $session->id)
            ->whereIn('status', ['open', 'pending'])
            ->latest()
            ->first();

        if ($conversation) {
            return $conversation;
        }

        // Create new conversation
        return Conversation::create([
            'account_id' => $session->account_id,
            'helpdesk_customer_id' => $customer->id,
            'chat_session_id' => $session->id,
            'status' => 'open',
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Load conversation for session.
     */
    public function loadConversations(ChatSession $session): array
    {
        if (! $session->helpdesk_customer_id) {
            return [];
        }

        $conversations = Conversation::where('chat_session_id', $session->id)
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
