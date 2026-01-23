<?php

namespace Modules\Campaign\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Campaign\Entities\CampaignField;
use Modules\Campaign\Entities\CampaignMaillist;
use Modules\Campaign\Entities\CampaignSegment;
use Modules\Campaign\Entities\CampaignSegmentCondition;

class CampaignSegmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates sample segments for mailing lists with filtering conditions.
     * Each segment components:
     * - Unique identifier (uid)
     * - Name and matching type (ANY or ALL)
     * - Segment conditions with field operators and values
     * - Association with mailing list
     */
    public function run(): void
    {
        // Get a mailing list to associate segments with
        $maillist = CampaignMaillist::first();

        if (! $maillist) {
            $this->command->warn('⚠️  No mailing list found. Please run CampaignMaillistSeeder first.');

            return;
        }

        // First, create some custom fields for the mailing list
        $this->seedFields($maillist);

        $segments = [
            [
                'maillist_id' => $maillist->id,
                'name' => 'Active Subscribers',
                'matching' => 'ALL',
                'conditions' => [
                    [
                        'field_id' => null,
                        'operator' => 'subscribed',
                        'value' => '1',
                    ],
                ],
            ],
            [
                'maillist_id' => $maillist->id,
                'name' => 'High Engagement Users',
                'matching' => 'ALL',
                'conditions' => [
                    [
                        'field_id' => null,
                        'operator' => 'opened_email',
                        'value' => '5',
                    ],
                    [
                        'field_id' => null,
                        'operator' => 'clicked_email',
                        'value' => '2',
                    ],
                ],
            ],
            [
                'maillist_id' => $maillist->id,
                'name' => 'Premium Members',
                'matching' => 'ANY',
                'conditions' => [
                    [
                        'field_id' => null,
                        'operator' => 'has_tag',
                        'value' => 'premium',
                    ],
                    [
                        'field_id' => null,
                        'operator' => 'has_tag',
                        'value' => 'vip',
                    ],
                ],
            ],
            [
                'maillist_id' => $maillist->id,
                'name' => 'Inactive for 30 Days',
                'matching' => 'ALL',
                'conditions' => [
                    [
                        'field_id' => null,
                        'operator' => 'no_open',
                        'value' => '30',
                    ],
                ],
            ],
            [
                'maillist_id' => $maillist->id,
                'name' => 'Recent Joiners',
                'matching' => 'ALL',
                'conditions' => [
                    [
                        'field_id' => null,
                        'operator' => 'subscribed_since',
                        'value' => '7',
                    ],
                ],
            ],
        ];

        $segmentCount = 0;

        foreach ($segments as $segmentData) {
            $conditions = $segmentData['conditions'];
            unset($segmentData['conditions']);

            $segment = CampaignSegment::firstOrCreate(
                [
                    'maillist_id' => $segmentData['maillist_id'],
                    'name' => $segmentData['name'],
                ],
                $segmentData
            );

            // Create segment conditions
            foreach ($conditions as $condition) {
                CampaignSegmentCondition::firstOrCreate(
                    [
                        'segment_id' => $segment->id,
                        'operator' => $condition['operator'],
                        'value' => $condition['value'],
                    ],
                    [
                        'uid' => Str::ulid(),
                        'field_id' => $condition['field_id'],
                    ]
                );
            }

            $segmentCount++;
        }

        $this->command->info('✅ Campaign segments seeded successfully ('.$segmentCount.' segments created)');
    }

    /**
     * Seed custom fields for mailing list.
     *
     * Creates common custom fields that can be used in segment conditions.
     */
    private function seedFields(CampaignMaillist $maillist): void
    {
        $fields = [
            [
                'label' => 'First Name',
                'tag' => 'FIRST_NAME',
                'type' => 'text',
                'visible' => 1,
                'required' => 0,
                'default_value' => null,
            ],
            [
                'label' => 'Last Name',
                'tag' => 'LAST_NAME',
                'type' => 'text',
                'visible' => 1,
                'required' => 0,
                'default_value' => null,
            ],
            [
                'label' => 'Company',
                'tag' => 'COMPANY',
                'type' => 'text',
                'visible' => 1,
                'required' => 0,
                'default_value' => null,
            ],
            [
                'label' => 'Location',
                'tag' => 'LOCATION',
                'type' => 'text',
                'visible' => 1,
                'required' => 0,
                'default_value' => null,
            ],
            [
                'label' => 'Industry',
                'tag' => 'INDUSTRY',
                'type' => 'text',
                'visible' => 1,
                'required' => 0,
                'default_value' => null,
            ],
            [
                'label' => 'Join Date',
                'tag' => 'JOIN_DATE',
                'type' => 'date',
                'visible' => 1,
                'required' => 0,
                'default_value' => null,
            ],
        ];

        foreach ($fields as $field) {
            CampaignField::firstOrCreate(
                [
                    'maillist_id' => $maillist->id,
                    'tag' => $field['tag'],
                ],
                array_merge($field, ['uid' => Str::ulid()])
            );
        }
    }
}
