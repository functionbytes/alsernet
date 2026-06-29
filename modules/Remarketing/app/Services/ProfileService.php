<?php

namespace Modules\Remarketing\Services;

use Modules\Remarketing\Models\Customer;
use Modules\Remarketing\Models\Event;
use Modules\Remarketing\Models\Store;

class ProfileService
{
    /**
     * Find or create a customer profile by email within a store.
     */
    public function findOrCreateByEmail(Store $store, string $email, array $attributes = []): Customer
    {
        $email = strtolower(trim($email));
        $emailHash = hash('sha256', $email);

        $customer = Customer::query()
            ->where('store_id', $store->id)
            ->where('email', $email)
            ->first();

        if ($customer) {
            if (! empty($attributes)) {
                $customer->fill($attributes)->save();
            }

            return $customer;
        }

        return Customer::query()->create(array_merge([
            'store_id' => $store->id,
            'email' => $email,
            'email_hash' => $emailHash,
            'status' => 'pending',
        ], $attributes));
    }

    /**
     * Associate all anonymous events from a visitor_id to a known customer.
     */
    public function mergeVisitorToCustomer(string $visitorId, Customer $customer): void
    {
        Event::query()
            ->where('visitor_id', $visitorId)
            ->whereNull('customer_id')
            ->update(['customer_id' => $customer->id]);
    }

    /**
     * Update or create a customer profile from a webhook payload.
     */
    public function updateFromWebhook(Store $store, array $payload): Customer
    {
        $email = strtolower(trim($payload['email'] ?? ''));

        if (empty($email)) {
            throw new \InvalidArgumentException('Webhook payload must include a valid email address.');
        }

        $attributes = array_filter([
            'first_name' => $payload['first_name'] ?? null,
            'last_name' => $payload['last_name'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'country' => $payload['country'] ?? null,
            'locale' => $payload['locale'] ?? null,
            'external_id' => $payload['external_id'] ?? null,
            'tags' => $payload['tags'] ?? null,
            'attributes' => $payload['attributes'] ?? null,
        ], fn ($value) => $value !== null);

        return $this->findOrCreateByEmail($store, $email, $attributes);
    }
}
