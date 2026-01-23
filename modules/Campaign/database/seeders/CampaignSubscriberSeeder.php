<?php

namespace Modules\Campaign\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Campaign\Entities\CampaignMaillist;
use Modules\Campaign\Entities\CampaignMaillistsSubscriber;
use App\Models\Subscriber\Subscriber;

class CampaignSubscriberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates sample subscribers for testing mailing lists and campaigns.
     * Each subscriber components:
     * - Unique identifier (uid)
     * - Association with mailing list
     * - Email verification status
     * - Optional tags for segmentation
     * - Subscription status
     */
    public function run(): void
    {
        // Get all mailing lists
        $maillists = CampaignMaillist::all();

        if ($maillists->isEmpty()) {
            $this->command->warn('⚠️  No mailing lists found. Please run CampaignMaillistSeeder first.');
            return;
        }

        // Sample subscriber data
        $subscriberEmails = [
            'john.smith@example.com',
            'sarah.johnson@example.com',
            'michael.brown@example.com',
            'emily.davis@example.com',
            'david.wilson@example.com',
            'jessica.miller@example.com',
            'robert.moore@example.com',
            'laura.taylor@example.com',
            'james.anderson@example.com',
            'patricia.thomas@example.com',
            'christopher.jackson@example.com',
            'jennifer.white@example.com',
            'matthew.harris@example.com',
            'mary.martin@example.com',
            'daniel.garcia@example.com',
        ];

        $tags = ['premium', 'vip', 'engaged', 'active', 'dormant'];
        $verificationStatuses = ['verified', 'unverified', 'verified'];
        $subscriberCount = 0;

        foreach ($maillists as $maillist) {
            foreach ($subscriberEmails as $index => $email) {
                // Create a subscriber if it doesn't exist
                $subscriber = Subscriber::firstOrCreate(
                    ['email' => $email],
                    [
                        'first_name' => $this->extractFirstName($email),
                        'last_name' => $this->extractLastName($email),
                    ]
                );

                // Create maillist subscriber association
                $maillistSubscriber = CampaignMaillistsSubscriber::firstOrCreate(
                    [
                        'maillist_id' => $maillist->id,
                        'subscriber_id' => $subscriber->id,
                    ],
                    [
                        'uid' => Str::ulid(),
                        'verification_status' => $verificationStatuses[$index % count($verificationStatuses)],
                        'tags' => json_encode(array_slice(
                            $tags,
                            $index % count($tags),
                            rand(1, 2)
                        )),
                    ]
                );

                $subscriberCount++;
            }
        }

        $this->command->info('✅ Campaign subscribers seeded successfully (' . $subscriberCount . ' subscriber associations created)');
    }

    /**
     * Extract first name from email address.
     *
     * @param string $email
     * @return string
     */
    private function extractFirstName(string $email): string
    {
        $name = explode('@', $email)[0];
        $parts = explode('.', $name);

        return ucfirst($parts[0] ?? 'User');
    }

    /**
     * Extract last name from email address.
     *
     * @param string $email
     * @return string
     */
    private function extractLastName(string $email): string
    {
        $name = explode('@', $email)[0];
        $parts = explode('.', $name);

        return ucfirst($parts[1] ?? 'Subscriber');
    }
}
