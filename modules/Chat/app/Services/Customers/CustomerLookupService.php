<?php

namespace Modules\Chat\Services\Customers;

use Modules\Chat\Models\Customers\Customer;

class CustomerLookupService
{
    public function findOrCreate(int $accountId, array $data): Customer
    {
        if (! empty($data['email'])) {
            $customer = Customer::where('account_id', $accountId)
                ->where('email', $data['email'])
                ->withTrashed()
                ->first();

            if ($customer) {
                if ($customer->trashed()) {
                    $customer->restore();
                }

                return $customer;
            }
        }

        if (! empty($data['phone_number'])) {
            $customer = Customer::where('account_id', $accountId)
                ->where('phone_number', $data['phone_number'])
                ->withTrashed()
                ->first();

            if ($customer) {
                if ($customer->trashed()) {
                    $customer->restore();
                }

                return $customer;
            }
        }

        return Customer::create([
            'account_id' => $accountId,
            'name' => $data['name'] ?? 'Anonymous',
            'email' => $data['email'] ?? null,
            'phone_number' => $data['phone_number'] ?? null,
            'last_activity_at' => now(),
        ]);
    }
}
