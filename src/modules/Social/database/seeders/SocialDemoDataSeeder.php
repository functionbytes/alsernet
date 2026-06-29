<?php

namespace Modules\Social\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Chat\Models\Accounts\Account;
use Modules\Social\Enums\PostStatus;
use Modules\Social\Enums\SocialNetwork;
use Modules\Social\Models\Campaign;
use Modules\Social\Models\HashtagGroup;
use Modules\Social\Models\Label;
use Modules\Social\Models\Post;
use Modules\Social\Models\SocialAccount;
use Modules\Social\Models\Template;

class SocialDemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first account or create one
        $account = Account::first();
        if (! $account) {
            $account = Account::create([
                'name' => 'Demo Company',
                'domain' => 'demo.example.com',
                'timezone' => 'UTC',
            ]);
        }

        // Get first user or create one
        $user = User::where('account_id', $account->id)->first();
        if (! $user) {
            $user = User::create([
                'account_id' => $account->id,
                'name' => 'Demo User',
                'email' => 'demo@example.com',
                'password' => bcrypt('password'),
            ]);
        }

        // Create Social Accounts
        $facebookAccount = SocialAccount::create([
            'account_id' => $account->id,
            'network' => SocialNetwork::FACEBOOK,
            'username' => 'democompany',
            'name' => 'Demo Company - Facebook',
            'access_token' => encrypt('demo_facebook_token_'.uniqid()),
            'status' => 1,
        ]);

        $instagramAccount = SocialAccount::create([
            'account_id' => $account->id,
            'network' => SocialNetwork::INSTAGRAM,
            'username' => 'democompany',
            'name' => 'Demo Company - Instagram',
            'access_token' => encrypt('demo_instagram_token_'.uniqid()),
            'status' => 1,
        ]);

        $twitterAccount = SocialAccount::create([
            'account_id' => $account->id,
            'network' => SocialNetwork::TWITTER,
            'username' => 'democompany',
            'name' => 'Demo Company - Twitter',
            'access_token' => encrypt('demo_twitter_token_'.uniqid()),
            'status' => 1,
        ]);

        $linkedinAccount = SocialAccount::create([
            'account_id' => $account->id,
            'network' => SocialNetwork::LINKEDIN,
            'username' => 'demo-company',
            'name' => 'Demo Company - LinkedIn',
            'access_token' => encrypt('demo_linkedin_token_'.uniqid()),
            'status' => 1,
        ]);

        // Create Campaigns
        $summerCampaign = Campaign::create([
            'account_id' => $account->id,
            'name' => 'Summer Sale 2025',
            'description' => 'Summer promotional campaign with 30% discount',
            'color' => '#FF6B6B',
            'start_date' => now(),
            'end_date' => now()->addMonths(2),
        ]);

        $productLaunchCampaign = Campaign::create([
            'account_id' => $account->id,
            'name' => 'Product Launch Q1',
            'description' => 'New product announcement campaign',
            'color' => '#4ECDC4',
            'start_date' => now()->addWeeks(2),
            'end_date' => now()->addMonths(3),
        ]);

        $brandAwarenessCampaign = Campaign::create([
            'account_id' => $account->id,
            'name' => 'Brand Awareness',
            'description' => 'Building brand presence on social media',
            'color' => '#95E1D3',
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonths(6),
        ]);

        // Create Labels
        $urgentLabel = Label::create([
            'account_id' => $account->id,
            'name' => 'Urgent',
            'color' => '#FF0000',
            'description' => 'Time-sensitive posts',
        ]);

        $promotionalLabel = Label::create([
            'account_id' => $account->id,
            'name' => 'Promotional',
            'color' => '#FFA500',
            'description' => 'Promotional content',
        ]);

        $educationalLabel = Label::create([
            'account_id' => $account->id,
            'name' => 'Educational',
            'color' => '#0000FF',
            'description' => 'Educational and informative posts',
        ]);

        // Create Hashtag Groups
        $techHashtags = HashtagGroup::create([
            'account_id' => $account->id,
            'name' => 'Technology',
            'hashtags' => json_encode(['#Tech', '#Innovation', '#Digital', '#AI', '#Technology']),
            'category' => 'Technology',
        ]);

        $businessHashtags = HashtagGroup::create([
            'account_id' => $account->id,
            'name' => 'Business',
            'hashtags' => json_encode(['#Business', '#Entrepreneurship', '#Startup', '#Growth', '#Success']),
            'category' => 'Business',
        ]);

        $marketingHashtags = HashtagGroup::create([
            'account_id' => $account->id,
            'name' => 'Marketing',
            'hashtags' => json_encode(['#Marketing', '#DigitalMarketing', '#SocialMedia', '#Branding', '#ContentMarketing']),
            'category' => 'Marketing',
        ]);

        // Create Templates
        $announcementTemplate = Template::create([
            'account_id' => $account->id,
            'name' => 'Product Announcement',
            'content' => "🎉 Exciting News! 🎉\n\nWe're thrilled to announce {{product_name}}!\n\n{{description}}\n\n✨ Key Features:\n• {{feature_1}}\n• {{feature_2}}\n• {{feature_3}}\n\nLearn more: {{link}}\n\n{{hashtags}}",
            'category' => 'Announcements',
        ]);

        $tipTemplate = Template::create([
            'account_id' => $account->id,
            'name' => 'Daily Tip',
            'content' => "💡 Tip of the Day!\n\n{{tip_content}}\n\n👉 Try this today and let us know how it goes!\n\n{{hashtags}}",
            'category' => 'Educational',
        ]);

        $promotionTemplate = Template::create([
            'account_id' => $account->id,
            'name' => 'Limited Offer',
            'content' => "🔥 LIMITED TIME OFFER! 🔥\n\n{{offer_description}}\n\n⏰ Offer ends: {{end_date}}\n\n🛒 Shop now: {{link}}\n\nDon't miss out!\n\n{{hashtags}}",
            'category' => 'Promotions',
        ]);

        // Create Sample Posts
        $publishedPost = Post::create([
            'account_id' => $account->id,
            'social_account_id' => $facebookAccount->id,
            'campaign_id' => $summerCampaign->id,
            'content' => "🌞 Summer is here! Enjoy 30% off on all our products!\n\nVisit our website now and use code: SUMMER2025\n\n#Summer #Sale #Discount #Shopping #Deals",
            'status' => PostStatus::PUBLISHED,
            'scheduled_at' => now()->subDays(3),
            'published_at' => now()->subDays(3),
            'created_by' => $user->id,
            'likes_count' => 145,
            'comments_count' => 23,
            'shares_count' => 12,
        ]);
        $publishedPost->labels()->attach([$promotionalLabel->id, $urgentLabel->id]);

        $scheduledPost = Post::create([
            'account_id' => $account->id,
            'social_account_id' => $instagramAccount->id,
            'campaign_id' => $productLaunchCampaign->id,
            'content' => "🚀 Get ready for something amazing!\n\nOur biggest product launch is coming next week. Stay tuned!\n\n#ComingSoon #ProductLaunch #Innovation #Excited #NewProduct",
            'status' => PostStatus::SCHEDULED,
            'scheduled_at' => now()->addDays(5),
            'created_by' => $user->id,
        ]);
        $scheduledPost->labels()->attach([$urgentLabel->id]);

        $draftPost = Post::create([
            'account_id' => $account->id,
            'social_account_id' => $twitterAccount->id,
            'campaign_id' => $brandAwarenessCampaign->id,
            'content' => "💡 Monday Motivation:\n\nSuccess is not final, failure is not fatal: it is the courage to continue that counts.\n\n#MondayMotivation #Inspiration #Success #Growth #Mindset",
            'status' => PostStatus::DRAFT,
            'created_by' => $user->id,
        ]);
        $draftPost->labels()->attach([$educationalLabel->id]);

        $approvalPost = Post::create([
            'account_id' => $account->id,
            'social_account_id' => $linkedinAccount->id,
            'campaign_id' => $brandAwarenessCampaign->id,
            'content' => "📊 Industry Insights: The Future of Digital Marketing\n\nDiscover the top 5 trends shaping digital marketing in 2025.\n\nRead our latest blog post: [link]\n\n#DigitalMarketing #Trends #Marketing #Business #Strategy",
            'status' => PostStatus::DRAFT,
            'created_by' => $user->id,
        ]);
        $approvalPost->labels()->attach([$educationalLabel->id]);

        $failedPost = Post::create([
            'account_id' => $account->id,
            'social_account_id' => $facebookAccount->id,
            'content' => "Test post with intentional error for demo purposes.\n\n#Testing #Demo",
            'status' => PostStatus::FAILED,
            'scheduled_at' => now()->subDay(),
            'created_by' => $user->id,
            'error_message' => 'Demo error: API token expired',
        ]);

        $this->command->info('✅ Social module demo data seeded successfully!');
        $this->command->info('📊 Created:');
        $this->command->info('   - 4 Social Accounts (Facebook, Instagram, Twitter, LinkedIn)');
        $this->command->info('   - 3 Campaigns');
        $this->command->info('   - 3 Labels');
        $this->command->info('   - 3 Hashtag Groups');
        $this->command->info('   - 3 Templates');
        $this->command->info('   - 5 Sample Posts (Published, Scheduled, Draft, Pending, Failed)');
    }
}
