<?php

namespace Modules\Chat\Services\Widget;

use Illuminate\Support\Facades\DB;
use Modules\Chat\Models\Channels\Web;
use Modules\Chat\Models\Customers\Customer;
use Modules\Chat\Models\Customers\CustomerInbox;

class WidgetContactService
{
    /**
     * Create or retrieve customer from widget data.
     */
    public function createContact(string $websiteToken, array $data): array
    {
        $webWidget = Web::where('website_token', $websiteToken)->first();

        if (! $webWidget) {
            throw new \Exception('Invalid widget token');
        }

        DB::beginTransaction();

        try {
            $customer = $this->findOrCreateCustomer(
                $webWidget->account_id,
                $data
            );

            $customerInbox = CustomerInbox::firstOrCreate([
                'customer_id' => $customer->id,
                'inbox_id' => $webWidget->inbox_id,
            ], [
                'source_id' => $customer->id,
            ]);

            DB::commit();

            return [
                'customer_id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone_number,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Find or create customer by email or phone.
     */
    protected function findOrCreateCustomer(int $accountId, array $data): Customer
    {
        $query = Customer::where('account_id', $accountId);

        if (! empty($data['email'])) {
            $customer = $query->where('email', $data['email'])->first();
            if ($customer) {
                return $customer;
            }
        }

        if (! empty($data['phone'])) {
            $customer = $query->where('phone_number', $data['phone'])->first();
            if ($customer) {
                return $customer;
            }
        }

        return Customer::create([
            'account_id' => $accountId,
            'name' => $data['name'] ?? 'Anonymous',
            'email' => $data['email'] ?? null,
            'phone_number' => $data['phone'] ?? null,
            'custom_attributes' => $data['custom_attributes'] ?? [],
            'last_activity_at' => now(),
        ]);
    }
}
