<?php

namespace Modules\Campaign\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Campaign\Entities\Automation\Automation;
use Modules\Campaign\Entities\CampaignMaillist;

class CampaignAutomationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates sample automation workflows for testing.
     * Each automation components:
     * - Unique identifier (uid)
     * - Name and timezone
     * - Automation flow data (trigger and email configuration)
     * - Status
     */
    public function run(): void
    {
        // Get a mailing list to associate automations with
        $maillist = CampaignMaillist::first();

        if (! $maillist) {
            $this->command->warn('⚠️  No mailing list found. Please run CampaignMaillistSeeder first.');

            return;
        }

        $automations = [
            [
                'uid' => Str::ulid(),
                'name' => 'Welcome Automation',
                'mail_list_id' => $maillist->id,
                'time_zone' => 'UTC',
                'status' => 'draft',
                'data' => $this->getWelcomeAutomationData(),
                'segment_id' => null,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Re-engagement Campaign',
                'mail_list_id' => $maillist->id,
                'time_zone' => 'UTC',
                'status' => 'draft',
                'data' => $this->getReEngagementAutomationData(),
                'segment_id' => null,
            ],
        ];

        foreach ($automations as $automation) {
            Automation::firstOrCreate(
                ['name' => $automation['name'], 'mail_list_id' => $automation['mail_list_id']],
                $automation
            );
        }

        $this->command->info('✅ Campaign automations seeded successfully ('.count($automations).' automations created)');
    }

    /**
     * Get welcome automation flow data.
     *
     * This automation sends a welcome email immediately when a subscriber joins,
     * followed by a follow-up email after 3 days.
     */
    private function getWelcomeAutomationData(): string
    {
        return json_encode([
            'elements' => [
                [
                    'uid' => Str::ulid(),
                    'type' => 'trigger',
                    'class' => 'modules\Campaign\Entities\Automation\Trigger',
                    'options' => [
                        'event' => 'subscribe',
                        'description' => 'When a subscriber joins the list',
                    ],
                    'position' => 0,
                ],
                [
                    'uid' => Str::ulid(),
                    'type' => 'send',
                    'class' => 'modules\Campaign\Entities\Automation\Send',
                    'options' => [
                        'subject' => 'Welcome to Our Community!',
                        'campaign_id' => null,
                        'delay_value' => 0,
                        'delay_unit' => 'minute',
                    ],
                    'position' => 1,
                ],
                [
                    'uid' => Str::ulid(),
                    'type' => 'wait',
                    'class' => 'modules\Campaign\Entities\Automation\Wait',
                    'options' => [
                        'delay_value' => 3,
                        'delay_unit' => 'day',
                    ],
                    'position' => 2,
                ],
                [
                    'uid' => Str::ulid(),
                    'type' => 'send',
                    'class' => 'modules\Campaign\Entities\Automation\Send',
                    'options' => [
                        'subject' => 'Check Out Our Latest Features',
                        'campaign_id' => null,
                        'delay_value' => 0,
                        'delay_unit' => 'minute',
                    ],
                    'position' => 3,
                ],
            ],
            'description' => 'Automatically send welcome emails to new subscribers with a follow-up after 3 days',
        ]);
    }

    /**
     * Get re-engagement automation flow data.
     *
     * This automation targets inactive subscribers and sends them a re-engagement email,
     * waits 7 days, then sends a special offer email.
     */
    private function getReEngagementAutomationData(): string
    {
        return json_encode([
            'elements' => [
                [
                    'uid' => Str::ulid(),
                    'type' => 'trigger',
                    'class' => 'modules\Campaign\Entities\Automation\Trigger',
                    'options' => [
                        'event' => 'no_open_30_days',
                        'description' => 'When a subscriber hasn\'t opened an email in 30 days',
                    ],
                    'position' => 0,
                ],
                [
                    'uid' => Str::ulid(),
                    'type' => 'send',
                    'class' => 'modules\Campaign\Entities\Automation\Send',
                    'options' => [
                        'subject' => 'We Miss You! Here\'s Something Special',
                        'campaign_id' => null,
                        'delay_value' => 0,
                        'delay_unit' => 'minute',
                    ],
                    'position' => 1,
                ],
                [
                    'uid' => Str::ulid(),
                    'type' => 'wait',
                    'class' => 'modules\Campaign\Entities\Automation\Wait',
                    'options' => [
                        'delay_value' => 7,
                        'delay_unit' => 'day',
                    ],
                    'position' => 2,
                ],
                [
                    'uid' => Str::ulid(),
                    'type' => 'send',
                    'class' => 'modules\Campaign\Entities\Automation\Send',
                    'options' => [
                        'subject' => 'Exclusive Offer: 30% Off Just for You',
                        'campaign_id' => null,
                        'delay_value' => 0,
                        'delay_unit' => 'minute',
                    ],
                    'position' => 3,
                ],
                [
                    'uid' => Str::ulid(),
                    'type' => 'wait',
                    'class' => 'modules\Campaign\Entities\Automation\Wait',
                    'options' => [
                        'delay_value' => 14,
                        'delay_unit' => 'day',
                    ],
                    'position' => 4,
                ],
                [
                    'uid' => Str::ulid(),
                    'type' => 'action',
                    'class' => 'modules\Campaign\Entities\Automation\Action',
                    'options' => [
                        'action' => 'unsubscribe_if_not_engaged',
                        'description' => 'Unsubscribe if subscriber still hasn\'t engaged',
                    ],
                    'position' => 5,
                ],
            ],
            'description' => 'Re-engage inactive subscribers with targeted emails and remove those who remain unengaged',
        ]);
    }
}
