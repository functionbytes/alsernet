<?php

namespace Modules\HelpdeskChat\Services\Contacts;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskChat\Models\Contacts\Contact;
use Modules\HelpdeskChat\Models\Conversations\Conversation;

class ContactMergeService
{
    /**
     * Find potential duplicate contacts.
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
        $duplicates = Contact::select('email', DB::raw('COUNT(*) as count'), DB::raw('GROUP_CONCAT(id) as contact_ids'))
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
                'contact_ids' => explode(',', $duplicate->contact_ids),
                'group_key' => 'email_'.$duplicate->email,
            ];
        });
    }

    /**
     * Find duplicates by phone number.
     */
    protected function findDuplicatesByPhone(int $accountId): Collection
    {
        $duplicates = Contact::select('phone_number', DB::raw('COUNT(*) as count'), DB::raw('GROUP_CONCAT(id) as contact_ids'))
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
                'contact_ids' => explode(',', $duplicate->contact_ids),
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
        $contacts = Contact::where('account_id', $accountId)
            ->whereNotNull('name')
            ->get(['id', 'name', 'email', 'phone_number']);

        $duplicates = collect();
        $processed = [];

        foreach ($contacts as $contact) {
            if (in_array($contact->id, $processed)) {
                continue;
            }

            $similar = $contacts->filter(function ($other) use ($contact, $processed) {
                if ($contact->id === $other->id || in_array($other->id, $processed)) {
                    return false;
                }

                $percent = 0;
                similar_text(
                    strtolower($contact->name),
                    strtolower($other->name),
                    $percent
                );

                return $percent >= 80;
            });

            if ($similar->isNotEmpty()) {
                $group = $similar->pluck('id')->push($contact->id)->toArray();
                $processed = array_merge($processed, $group);

                $duplicates->push([
                    'type' => 'name_similarity',
                    'value' => $contact->name,
                    'count' => count($group),
                    'contact_ids' => $group,
                    'group_key' => 'name_'.md5($contact->name),
                ]);
            }
        }

        return $duplicates;
    }

    /**
     * Merge multiple contacts into one primary contact.
     */
    public function mergeContacts(int $primaryContactId, array $duplicateContactIds): Contact
    {
        return DB::transaction(function () use ($primaryContactId, $duplicateContactIds) {
            $primaryContact = Contact::findOrFail($primaryContactId);
            $duplicates = Contact::whereIn('id', $duplicateContactIds)->get();

            foreach ($duplicates as $duplicate) {
                // Skip if trying to merge with self
                if ($duplicate->id === $primaryContact->id) {
                    continue;
                }

                // Merge data
                $this->mergeContactData($primaryContact, $duplicate);

                // Reassign conversation
                Conversation::where('contact_id', $duplicate->id)
                    ->update(['contact_id' => $primaryContact->id]);

                // Reassign contact inboxes
                DB::table('contact_inboxes')
                    ->where('contact_id', $duplicate->id)
                    ->update(['contact_id' => $primaryContact->id]);

                // Merge labels (avoid duplicates)
                $existingLabelIds = $primaryContact->labels()->pluck('labels.id')->toArray();
                $duplicateLabelIds = $duplicate->labels()->pluck('labels.id')->toArray();
                $newLabelIds = array_diff($duplicateLabelIds, $existingLabelIds);

                if (! empty($newLabelIds)) {
                    $primaryContact->labels()->attach($newLabelIds);
                }

                // Merge notes
                DB::table('contact_notes')
                    ->where('contact_id', $duplicate->id)
                    ->update(['contact_id' => $primaryContact->id]);

                // Delete duplicate
                $duplicate->delete();
            }

            return $primaryContact->fresh();
        });
    }

    /**
     * Merge data from duplicate into primary contact.
     */
    protected function mergeContactData(Contact $primary, Contact $duplicate): void
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
    public function previewMerge(int $primaryContactId, array $duplicateContactIds): array
    {
        $primaryContact = Contact::with(['labels', 'conversation'])->findOrFail($primaryContactId);
        $duplicates = Contact::with(['labels', 'conversation'])
            ->whereIn('id', $duplicateContactIds)
            ->get();

        $preview = [
            'primary' => [
                'id' => $primaryContact->id,
                'name' => $primaryContact->name,
                'email' => $primaryContact->email,
                'phone' => $primaryContact->phone_number,
                'conversations_count' => $primaryContact->conversations->count(),
                'labels_count' => $primaryContact->labels->count(),
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
                'total_conversations' => $primaryContact->conversations->count() + $duplicates->sum(fn ($d) => $d->conversations->count()),
                'total_labels' => $primaryContact->labels->count() + $duplicates->sum(fn ($d) => $d->labels->count()),
                'fields_to_update' => $this->getFieldsToUpdate($primaryContact, $duplicates),
            ],
        ];

        return $preview;
    }

    /**
     * Get list of fields that will be updated.
     */
    protected function getFieldsToUpdate(Contact $primary, Collection $duplicates): array
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
