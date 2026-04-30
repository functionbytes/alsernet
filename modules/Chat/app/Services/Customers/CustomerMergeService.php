<?php

namespace Modules\Chat\Services\Customers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Customers\Customer;

class CustomerMergeService
{
    /**
     * Find potential duplicate customers.
     */
    public function findDuplicates(int $accountId, array $criteria = []): Collection
    {
        $duplicates = collect();

        // Find duplicates by email
        if (! isset($criteria['skip_email'])) {
            $emailDuplicates = $this->findDuplicatesByEmail($accountId);
            $duplicates = $duplicates->merge($emailDuplicates);
        }

        // Find duplicates by phone
        if (! isset($criteria['skip_phone'])) {
            $phoneDuplicates = $this->findDuplicatesByPhone($accountId);
            $duplicates = $duplicates->merge($phoneDuplicates);
        }

        // Find duplicates by name similarity
        if (isset($criteria['include_name_similarity'])) {
            $nameDuplicates = $this->findDuplicatesByNameSimilarity($accountId);
            $duplicates = $duplicates->merge($nameDuplicates);
        }

        return $duplicates->unique('group_key');
    }

    /**
     * Find duplicates by email.
     */
    protected function findDuplicatesByEmail(int $accountId): Collection
    {
        $duplicates = Customer::select('email', DB::raw('COUNT(*) as count'), DB::raw('GROUP_CONCAT(id) as customer_ids'))
            ->where('account_id', $accountId)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->groupBy('email')
            ->having('count', '>', 1)
            ->get();

        return $duplicates->map(function ($duplicate) {
            return [
                'type' => 'email',
                'value' => $duplicate->email,
                'count' => $duplicate->count,
                'customer_ids' => explode(',', $duplicate->customer_ids),
                'group_key' => 'email_'.$duplicate->email,
            ];
        });
    }

    /**
     * Find duplicates by phone number.
     */
    protected function findDuplicatesByPhone(int $accountId): Collection
    {
        $duplicates = Customer::select('phone_number', DB::raw('COUNT(*) as count'), DB::raw('GROUP_CONCAT(id) as customer_ids'))
            ->where('account_id', $accountId)
            ->whereNotNull('phone_number')
            ->where('phone_number', '!=', '')
            ->groupBy('phone_number')
            ->having('count', '>', 1)
            ->get();

        return $duplicates->map(function ($duplicate) {
            return [
                'type' => 'phone',
                'value' => $duplicate->phone_number,
                'count' => $duplicate->count,
                'customer_ids' => explode(',', $duplicate->customer_ids),
                'group_key' => 'phone_'.$duplicate->phone_number,
            ];
        });
    }

    /**
     * Find duplicates by name similarity.
     */
    protected function findDuplicatesByNameSimilarity(int $accountId): Collection
    {
        // This is a simple implementation - could be enhanced with fuzzy matching
        $customers = Customer::where('account_id', $accountId)
            ->whereNotNull('name')
            ->get(['id', 'name', 'email', 'phone_number']);

        $duplicates = collect();
        $processed = [];

        foreach ($customers as $customer) {
            if (in_array($customer->id, $processed)) {
                continue;
            }

            $similar = $customers->filter(function ($other) use ($customer, $processed) {
                if ($customer->id === $other->id || in_array($other->id, $processed)) {
                    return false;
                }

                $percent = 0;
                similar_text(
                    strtolower($customer->name),
                    strtolower($other->name),
                    $percent
                );

                return $percent >= 80;
            });

            if ($similar->isNotEmpty()) {
                $group = $similar->pluck('id')->push($customer->id)->toArray();
                $processed = array_merge($processed, $group);

                $duplicates->push([
                    'type' => 'name_similarity',
                    'value' => $customer->name,
                    'count' => count($group),
                    'customer_ids' => $group,
                    'group_key' => 'name_'.md5($customer->name),
                ]);
            }
        }

        return $duplicates;
    }

    /**
     * Merge multiple customers into one primary customer.
     */
    public function mergeCustomers(int $primaryCustomerId, array $duplicateCustomerIds): Customer
    {
        return DB::transaction(function () use ($primaryCustomerId, $duplicateCustomerIds) {
            $primaryCustomer = Customer::findOrFail($primaryCustomerId);
            $duplicates = Customer::whereIn('id', $duplicateCustomerIds)->get();

            foreach ($duplicates as $duplicate) {
                // Skip if trying to merge with self
                if ($duplicate->id === $primaryCustomer->id) {
                    continue;
                }

                // Merge data
                $this->mergeCustomerData($primaryCustomer, $duplicate);

                // Reassign conversation
                Conversation::where('customer_id', $duplicate->id)
                    ->update(['customer_id' => $primaryCustomer->id]);

                // Reassign customer inboxes
                DB::table('customer_inboxes')
                    ->where('customer_id', $duplicate->id)
                    ->update(['customer_id' => $primaryCustomer->id]);

                // Merge labels (avoid duplicates)
                $existingLabelIds = $primaryCustomer->labels()->pluck('labels.id')->toArray();
                $duplicateLabelIds = $duplicate->labels()->pluck('labels.id')->toArray();
                $newLabelIds = array_diff($duplicateLabelIds, $existingLabelIds);

                if (! empty($newLabelIds)) {
                    $primaryCustomer->labels()->attach($newLabelIds);
                }

                // Merge notes
                DB::table('customer_notes')
                    ->where('customer_id', $duplicate->id)
                    ->update(['customer_id' => $primaryCustomer->id]);

                // Delete duplicate
                $duplicate->delete();
            }

            return $primaryCustomer->fresh();
        });
    }

    /**
     * Merge data from duplicate into primary customer.
     */
    protected function mergeCustomerData(Customer $primary, Customer $duplicate): void
    {
        $updates = [];

        // Merge basic fields (use duplicate if primary is empty)
        if (empty($primary->last_name) && ! empty($duplicate->last_name)) {
            $updates['last_name'] = $duplicate->last_name;
        }

        if (empty($primary->email) && ! empty($duplicate->email)) {
            $updates['email'] = $duplicate->email;
        }

        if (empty($primary->phone_number) && ! empty($duplicate->phone_number)) {
            $updates['phone_number'] = $duplicate->phone_number;
        }

        if (empty($primary->company_name) && ! empty($duplicate->company_name)) {
            $updates['company_name'] = $duplicate->company_name;
        }

        if (empty($primary->bio) && ! empty($duplicate->bio)) {
            $updates['bio'] = $duplicate->bio;
        }

        if (empty($primary->city) && ! empty($duplicate->city)) {
            $updates['city'] = $duplicate->city;
        }

        if (empty($primary->country) && ! empty($duplicate->country)) {
            $updates['country'] = $duplicate->country;
        }

        // Merge additional attributes
        $primaryAttrs = $primary->additional_attributes ?? [];
        $duplicateAttrs = $duplicate->additional_attributes ?? [];
        $mergedAttrs = array_merge($duplicateAttrs, $primaryAttrs); // Primary takes precedence

        if ($mergedAttrs !== $primaryAttrs) {
            $updates['additional_attributes'] = $mergedAttrs;
        }

        // Merge social profiles
        $primarySocial = $primary->social_profiles ?? [];
        $duplicateSocial = $duplicate->social_profiles ?? [];
        $mergedSocial = array_merge($duplicateSocial, $primarySocial);

        if ($mergedSocial !== $primarySocial) {
            $updates['social_profiles'] = $mergedSocial;
        }

        // Update last activity if duplicate is more recent
        if ($duplicate->last_activity_at && (! $primary->last_activity_at || $duplicate->last_activity_at > $primary->last_activity_at)) {
            $updates['last_activity_at'] = $duplicate->last_activity_at;
        }

        if (! empty($updates)) {
            $primary->update($updates);
        }
    }

    /**
     * Preview what would be merged.
     */
    public function previewMerge(int $primaryCustomerId, array $duplicateCustomerIds): array
    {
        $primaryCustomer = Customer::with(['labels', 'conversations'])->findOrFail($primaryCustomerId);
        $duplicates = Customer::with(['labels', 'conversations'])
            ->whereIn('id', $duplicateCustomerIds)
            ->get();

        $preview = [
            'primary' => [
                'id' => $primaryCustomer->id,
                'name' => $primaryCustomer->name,
                'email' => $primaryCustomer->email,
                'phone' => $primaryCustomer->phone_number,
                'conversations_count' => $primaryCustomer->conversations->count(),
                'labels_count' => $primaryCustomer->labels->count(),
            ],
            'duplicates' => $duplicates->map(function ($duplicate) {
                return [
                    'id' => $duplicate->id,
                    'name' => $duplicate->name,
                    'email' => $duplicate->email,
                    'phone' => $duplicate->phone_number,
                    'conversations_count' => $duplicate->conversations->count(),
                    'labels_count' => $duplicate->labels->count(),
                ];
            })->toArray(),
            'after_merge' => [
                'total_conversations' => $primaryCustomer->conversations->count() + $duplicates->sum(fn ($d) => $d->conversations->count()),
                'total_labels' => $primaryCustomer->labels->count() + $duplicates->sum(fn ($d) => $d->labels->count()),
                'fields_to_update' => $this->getFieldsToUpdate($primaryCustomer, $duplicates),
            ],
        ];

        return $preview;
    }

    /**
     * Get list of fields that will be updated.
     */
    protected function getFieldsToUpdate(Customer $primary, Collection $duplicates): array
    {
        $updates = [];

        foreach ($duplicates as $duplicate) {
            if (empty($primary->last_name) && ! empty($duplicate->last_name)) {
                $updates[] = 'last_name';
            }
            if (empty($primary->email) && ! empty($duplicate->email)) {
                $updates[] = 'email';
            }
            if (empty($primary->phone_number) && ! empty($duplicate->phone_number)) {
                $updates[] = 'phone_number';
            }
            if (empty($primary->company_name) && ! empty($duplicate->company_name)) {
                $updates[] = 'company_name';
            }
        }

        return array_unique($updates);
    }
}
