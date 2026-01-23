<?php

namespace Modules\Campaign\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Campaign\Entities\CampaignMaillist;

class CampaignMaillistSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates sample mailing lists for testing campaigns.
     * Each mailing list components:
     * - Unique identifier (uid)
     * - Title and sender information
     * - Email configuration
     */
    public function run(): void
    {
        $maillists = [
            [
                'uid' => Str::ulid(),
                'title' => 'Main Newsletter',
                'from_email' => 'newsletter@example.com',
                'from_name' => 'Newsletter Team',
                'default_subject' => 'Latest Updates from Our Team',
                'remind_message' => 'Thank you for subscribing to our newsletter',
                'send_welcome_email' => 1,
                'unsubscribe_notification' => 1,
                'subscribe_confirmation' => 1,
                'all_sending_servers' => 0,
                'available' => 'yes',
                'custom_order' => 1,
            ],
            [
                'uid' => Str::ulid(),
                'title' => 'Product Updates',
                'from_email' => 'product@example.com',
                'from_name' => 'Product Team',
                'default_subject' => 'Exciting New Product Releases',
                'remind_message' => 'Stay updated with our latest product launches',
                'send_welcome_email' => 1,
                'unsubscribe_notification' => 0,
                'subscribe_confirmation' => 0,
                'all_sending_servers' => 1,
                'available' => 'yes',
                'custom_order' => 2,
            ],
            [
                'uid' => Str::ulid(),
                'title' => 'Promotional Campaigns',
                'from_email' => 'promo@example.com',
                'from_name' => 'Sales Department',
                'default_subject' => 'Special Offers Just for You',
                'remind_message' => 'Exclusive deals and promotions for our subscribers',
                'send_welcome_email' => 0,
                'unsubscribe_notification' => 1,
                'subscribe_confirmation' => 0,
                'all_sending_servers' => 0,
                'available' => 'yes',
                'custom_order' => 3,
            ],
            [
                'uid' => Str::ulid(),
                'title' => 'Weekly Digest',
                'from_email' => 'digest@example.com',
                'from_name' => 'Content Team',
                'default_subject' => 'Your Weekly Digest',
                'remind_message' => 'Get your weekly dose of curated content',
                'send_welcome_email' => 1,
                'unsubscribe_notification' => 1,
                'subscribe_confirmation' => 1,
                'all_sending_servers' => 1,
                'available' => 'yes',
                'custom_order' => 4,
            ],
            [
                'uid' => Str::ulid(),
                'title' => 'Event Notifications',
                'from_email' => 'events@example.com',
                'from_name' => 'Events Team',
                'default_subject' => 'Upcoming Events and Webinars',
                'remind_message' => 'Never miss an important event or webinar',
                'send_welcome_email' => 0,
                'unsubscribe_notification' => 0,
                'subscribe_confirmation' => 1,
                'all_sending_servers' => 0,
                'available' => 'yes',
                'custom_order' => 5,
            ],
        ];

        foreach ($maillists as $maillist) {
            CampaignMaillist::firstOrCreate(
                ['title' => $maillist['title']],
                $maillist
            );
        }

        $this->command->info('✅ Campaign mailing lists seeded successfully ('.count($maillists).' lists created)');
    }
}
