<?php

namespace Modules\Chat\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Customers\Customer;

class WebhookCacheService
{
    /**
     * Cache TTL in seconds (5 minutes).
     */
    private const CACHE_TTL = 300;

    /**
     * Cache prefix for customer lookups.
     */
    private const CUSTOMER_PREFIX = 'webhook:customer:';

    /**
     * Cache prefix for conversation lookups.
     */
    private const CONVERSATION_PREFIX = 'webhook:conversation:';

    /**
     * Get customer from cache or database.
     *
     * Caches by (account_id, identifier) to avoid duplicate queries
     * for the same customer across multiple webhook events.
     */
    public function cacheGetCustomer(int $accountId, string $identifier): ?Customer
    {
        $cacheKey = self::CUSTOMER_PREFIX.$accountId.':'.$identifier;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($accountId, $identifier) {
            return Customer::where('account_id', $accountId)
                ->where('identifier', $identifier)
                ->first();
        });
    }

    /**
     * Get open conversation from cache or database.
     *
     * Caches by (account_id, customer_id, inbox_id) to avoid duplicate queries
     * for the same conversation across multiple webhook events.
     */
    public function cacheGetConversation(int $accountId, int $customerId, int $inboxId): ?Conversation
    {
        $cacheKey = self::CONVERSATION_PREFIX.$accountId.':'.$customerId.':'.$inboxId;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($accountId, $customerId, $inboxId) {
            return Conversation::where('account_id', $accountId)
                ->where('customer_id', $customerId)
                ->where('inbox_id', $inboxId)
                ->whereNull('closed_at')
                ->first();
        });
    }

    /**
     * Invalidate customer cache when customer is updated.
     *
     * Should be called from Customer model observer on created/updated events.
     */
    public function invalidateCustomerCache(int $customerId): void
    {
        $customer = Customer::find($customerId);

        if (! $customer) {
            return;
        }

        $cacheKey = self::CUSTOMER_PREFIX.$customer->account_id.':'.$customer->identifier;
        Cache::forget($cacheKey);
    }

    /**
     * Invalidate conversation cache when conversation is updated.
     *
     * Should be called from Conversation model observer on created/updated/deleted events.
     */
    public function invalidateConversationCache(int $conversationId): void
    {
        $conversation = Conversation::find($conversationId);

        if (! $conversation) {
            return;
        }

        $cacheKey = self::CONVERSATION_PREFIX.$conversation->account_id.':'.$conversation->customer_id.':'.$conversation->inbox_id;
        Cache::forget($cacheKey);
    }

    /**
     * Store customer in cache after creation.
     *
     * Called from jobs after creating a new customer to warm the cache
     * and avoid immediate cache miss on next webhook event.
     */
    public function cacheSetCustomer(Customer $customer): void
    {
        $cacheKey = self::CUSTOMER_PREFIX.$customer->account_id.':'.$customer->identifier;
        Cache::put($cacheKey, $customer, self::CACHE_TTL);
    }

    /**
     * Store conversation in cache after creation.
     *
     * Called from jobs after creating a new conversation to warm the cache
     * and avoid immediate cache miss on next webhook event.
     */
    public function cacheSetConversation(Conversation $conversation): void
    {
        $cacheKey = self::CONVERSATION_PREFIX.$conversation->account_id.':'.$conversation->customer_id.':'.$conversation->inbox_id;
        Cache::put($cacheKey, $conversation, self::CACHE_TTL);
    }
}
