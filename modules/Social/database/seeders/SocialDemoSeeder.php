<?php

namespace Modules\Social\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Social\Models\Campaign;
use Modules\Social\Models\Post;
use Modules\Social\Models\SocialAccount;
use Modules\Social\Models\SocialListeningKeyword;

class SocialDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding Social Media demo data...');

        // Create social accounts (4 networks)
        $this->command->info('Creating social accounts...');
        $accounts = $this->createSocialAccounts();

        // Create campaigns
        $this->command->info('Creating campaigns...');
        $campaigns = $this->createCampaigns();

        // Create posts with different statuses
        $this->command->info('Creating posts...');
        $this->createPosts($accounts, $campaigns);

        // Create social listening keywords
        $this->command->info('Creating social listening keywords...');
        $this->createListeningKeywords();

        $this->command->info('✅ Social Media demo data seeded successfully!');
    }

    /**
     * Create demo social accounts
     */
    protected function createSocialAccounts(): array
    {
        return [
            SocialAccount::factory()->facebook()->active()->create([
                'name' => 'Demo Company Facebook Page',
                'username' => 'democompany',
                'follower_count' => 5420,
            ]),
            SocialAccount::factory()->instagram()->active()->create([
                'name' => '@democompany',
                'username' => 'democompany',
                'follower_count' => 12500,
            ]),
            SocialAccount::factory()->twitter()->active()->create([
                'name' => '@DemoCompany',
                'username' => 'democompany',
                'follower_count' => 3200,
            ]),
            SocialAccount::factory()->linkedin()->active()->create([
                'name' => 'Demo Company Ltd',
                'username' => 'demo-company',
                'follower_count' => 8900,
            ]),
        ];
    }

    /**
     * Create demo campaigns
     */
    protected function createCampaigns(): array
    {
        return [
            Campaign::factory()->active()->create([
                'name' => 'Summer Sale 2025',
                'description' => 'Summer promotional campaign',
                'color' => '#FF6B6B',
                'start_date' => now()->subDays(30),
                'end_date' => now()->addDays(30),
            ]),
            Campaign::factory()->active()->create([
                'name' => 'Product Launch',
                'description' => 'New product announcement campaign',
                'color' => '#4ECDC4',
                'start_date' => now()->subDays(15),
                'end_date' => now()->addDays(15),
            ]),
            Campaign::factory()->active()->create([
                'name' => 'Brand Awareness',
                'description' => 'General brand awareness content',
                'color' => '#95E1D3',
                'start_date' => now()->subDays(60),
                'end_date' => null,
            ]),
        ];
    }

    /**
     * Create demo posts across all networks and statuses
     */
    protected function createPosts(array $accounts, array $campaigns): void
    {
        foreach ($accounts as $account) {
            // Published posts with good performance (30 posts per account)
            Post::factory()
                ->count(30)
                ->published()
                ->create([
                    'social_account_id' => $account->id,
                    'campaign_id' => fake()->randomElement($campaigns)->id,
                ]);

            // High performing posts (5 per account)
            Post::factory()
                ->count(5)
                ->highPerformance()
                ->create([
                    'social_account_id' => $account->id,
                    'campaign_id' => fake()->randomElement($campaigns)->id,
                ]);

            // Scheduled posts (10 per account)
            Post::factory()
                ->count(10)
                ->scheduled()
                ->create([
                    'social_account_id' => $account->id,
                    'campaign_id' => fake()->randomElement($campaigns)->id,
                ]);

            // Draft posts (5 per account)
            Post::factory()
                ->count(5)
                ->draft()
                ->create([
                    'social_account_id' => $account->id,
                    'campaign_id' => null,
                ]);

            // Failed posts (2 per account)
            Post::factory()
                ->count(2)
                ->failed()
                ->create([
                    'social_account_id' => $account->id,
                    'campaign_id' => fake()->randomElement($campaigns)->id,
                ]);
        }

        $this->command->info('   Created '.Post::count().' posts');
    }

    /**
     * Create demo social listening keywords
     */
    protected function createListeningKeywords(): void
    {
        $keywords = [
            [
                'name' => 'democompany',
                'type' => 'mention',
                'networks' => ['facebook', 'instagram', 'twitter'],
            ],
            [
                'name' => 'productlaunch',
                'type' => 'hashtag',
                'networks' => ['instagram', 'twitter'],
            ],
            [
                'name' => 'summer sale',
                'type' => 'keyword',
                'networks' => ['facebook', 'instagram', 'twitter', 'linkedin'],
            ],
            [
                'name' => 'competitor inc',
                'type' => 'competitor',
                'networks' => ['twitter', 'linkedin'],
            ],
        ];

        foreach ($keywords as $keywordData) {
            SocialListeningKeyword::factory()->active()->create($keywordData);
        }

        $this->command->info('   Created '.count($keywords).' listening keywords');
    }
}
