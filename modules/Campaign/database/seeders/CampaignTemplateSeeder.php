<?php

namespace Modules\Campaign\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Campaign\Entities\Campaign;
use Modules\Campaign\Entities\CampaignMaillist;

class CampaignTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates sample email campaign templates for testing.
     * Each template components:
     * - Unique identifier (uid)
     * - HTML content
     * - Subject line and sender information
     * - Tracking backups
     */
    public function run(): void
    {
        // Get a mailing list to associate campaigns with
        $maillist = CampaignMaillist::first();

        if (! $maillist) {
            $this->command->warn('⚠️  No mailing list found. Please run CampaignMaillistSeeder first.');

            return;
        }

        $templates = [
            [
                'uid' => Str::ulid(),
                'type' => 'regular',
                'title' => 'Welcome Email Template',
                'subject' => 'Welcome to Our Community!',
                'from_email' => $maillist->from_email,
                'from_name' => $maillist->from_name,
                'reply_to' => $maillist->from_email,
                'status' => 'draft',
                'track_open' => 1,
                'track_click' => 1,
                'sign_dkim' => 0,
                'plain' => $this->getPlainTextWelcome(),
                'template_source' => $this->getHtmlTemplateWelcome(),
                'preheader' => 'We are excited to have you on board',
                'default_maillist_id' => $maillist->id,
            ],
            [
                'uid' => Str::ulid(),
                'type' => 'regular',
                'title' => 'Promotional Campaign Template',
                'subject' => 'Exclusive Offer - Limited Time Only!',
                'from_email' => $maillist->from_email,
                'from_name' => $maillist->from_name,
                'reply_to' => $maillist->from_email,
                'status' => 'draft',
                'track_open' => 1,
                'track_click' => 1,
                'sign_dkim' => 0,
                'plain' => $this->getPlainTextPromo(),
                'template_source' => $this->getHtmlTemplatePromo(),
                'preheader' => 'Don\'t miss out on this amazing deal',
                'default_maillist_id' => $maillist->id,
            ],
            [
                'uid' => Str::ulid(),
                'type' => 'regular',
                'title' => 'Newsletter Template',
                'subject' => 'Your Weekly Newsletter',
                'from_email' => $maillist->from_email,
                'from_name' => $maillist->from_name,
                'reply_to' => $maillist->from_email,
                'status' => 'draft',
                'track_open' => 1,
                'track_click' => 1,
                'sign_dkim' => 0,
                'plain' => $this->getPlainTextNewsletter(),
                'template_source' => $this->getHtmlTemplateNewsletter(),
                'preheader' => 'This week\'s most important updates',
                'default_maillist_id' => $maillist->id,
            ],
        ];

        foreach ($templates as $template) {
            Campaign::firstOrCreate(
                ['title' => $template['title']],
                $template
            );
        }

        $this->command->info('✅ Campaign templates seeded successfully ('.count($templates).' templates created)');
    }

    /**
     * Get welcome email HTML template.
     */
    private function getHtmlTemplateWelcome(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background-color: #f9f9f9; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
        .button { display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to Our Community!</h1>
        </div>
        <div class="content">
            <p>Hi there,</p>
            <p>Thank you for joining our community. We're excited to have you on board and look forward to sharing valuable content with you.</p>
            <p>Here's what you can expect from us:</p>
            <ul>
                <li>Exclusive tips and insights</li>
                <li>Special offers for subscribers</li>
                <li>Industry news and updates</li>
                <li>Early access to new features</li>
            </ul>
            <p style="text-align: center; margin: 30px 0;">
                <a href="#" class="button">Get Started</a>
            </p>
            <p>If you have any questions, feel free to reach out to us.</p>
            <p>Best regards,<br>The Team</p>
        </div>
        <div class="footer">
            <p>&copy; 2024 Our Company. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Get promotional email HTML template.
     */
    private function getHtmlTemplatePromo(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #ff6b6b; color: white; padding: 20px; text-align: center; }
        .promo { background-color: #fff3cd; border-left: 4px solid #ff6b6b; padding: 20px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
        .button { display: inline-block; padding: 12px 30px; background-color: #ff6b6b; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Special Offer Inside!</h1>
        </div>
        <div class="promo">
            <h2 style="margin-top: 0;">50% OFF Your Next Purchase!</h2>
            <p style="font-size: 18px; color: #ff6b6b; font-weight: bold;">Limited Time Offer - Valid This Week Only</p>
            <p>We're offering an exclusive 50% discount on all products, just for our valued subscribers like you.</p>
            <p style="text-align: center; margin: 30px 0;">
                <a href="#" class="button">Claim Your Discount</a>
            </p>
            <p>Use code: <strong>SAVE50</strong> at checkout</p>
        </div>
        <div style="padding: 20px; background-color: #f9f9f9;">
            <p>Don't miss out on this incredible opportunity. Offer ends this Friday!</p>
            <p>Best regards,<br>The Sales Team</p>
        </div>
        <div class="footer">
            <p>&copy; 2024 Our Company. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Get newsletter email HTML template.
     */
    private function getHtmlTemplateNewsletter(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #2c3e50; color: white; padding: 20px; text-align: center; }
        .article { border-bottom: 1px solid #ddd; padding: 20px 0; }
        .article h3 { margin-top: 0; color: #2c3e50; }
        .read-more { color: #007bff; text-decoration: none; font-weight: bold; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📰 Your Weekly Newsletter</h1>
            <p>Week of December 29, 2024</p>
        </div>
        <div style="padding: 20px; background-color: #f9f9f9;">
            <div class="article">
                <h3>Top Story: Industry Trends in 2024</h3>
                <p>This week we look at the major trends shaping our industry and what to expect in the coming months.</p>
                <p><a href="#" class="read-more">Read More →</a></p>
            </div>
            <div class="article">
                <h3>New Feature Release: Advanced Analytics</h3>
                <p>We're excited to announce the launch of our new advanced analytics dashboard designed to give you deeper insights.</p>
                <p><a href="#" class="read-more">Read More →</a></p>
            </div>
            <div class="article">
                <h3>Customer Success Story: Case Study</h3>
                <p>Learn how one of our customers increased their productivity by 300% using our platform.</p>
                <p><a href="#" class="read-more">Read More →</a></p>
            </div>
            <div style="background-color: #e8f4f8; padding: 15px; border-radius: 5px; margin-top: 20px;">
                <p><strong>Tip of the Week:</strong> Did you know you can customize your dashboard? Visit your backups to personalize your experience.</p>
            </div>
        </div>
        <div class="footer">
            <p>&copy; 2024 Our Company. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Get plain text welcome email.
     */
    private function getPlainTextWelcome(): string
    {
        return <<<'TEXT'
Welcome to Our Community!

Hi there,

Thank you for joining our community. We're excited to have you on board and look forward to sharing valuable content with you.

Here's what you can expect from us:
- Exclusive tips and insights
- Special offers for subscribers
- Industry news and updates
- Early access to new features

If you have any questions, feel free to reach out to us.

Best regards,
The Team

© 2024 Our Company. All rights reserved.
TEXT;
    }

    /**
     * Get plain text promotional email.
     */
    private function getPlainTextPromo(): string
    {
        return <<<'TEXT'
🎉 SPECIAL OFFER INSIDE! 🎉

50% OFF Your Next Purchase!

We're offering an exclusive 50% discount on all products, just for our valued subscribers like you.

Use code: SAVE50 at checkout

Limited Time Offer - Valid This Week Only
Don't miss out on this incredible opportunity. Offer ends this Friday!

Best regards,
The Sales Team

© 2024 Our Company. All rights reserved.
TEXT;
    }

    /**
     * Get plain text newsletter email.
     */
    private function getPlainTextNewsletter(): string
    {
        return <<<'TEXT'
📰 YOUR WEEKLY NEWSLETTER
Week of December 29, 2024

TOP STORY: Industry Trends in 2024
This week we look at the major trends shaping our industry and what to expect in the coming months.

NEW FEATURE RELEASE: Advanced Analytics
We're excited to announce the launch of our new advanced analytics dashboard designed to give you deeper insights.

CUSTOMER SUCCESS STORY: Case Study
Learn how one of our customers increased their productivity by 300% using our platform.

TIP OF THE WEEK:
Did you know you can customize your dashboard? Visit your backups to personalize your experience.

© 2024 Our Company. All rights reserved.
TEXT;
    }
}
