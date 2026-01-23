<?php

namespace Modules\Campaign\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Campaign\Entities\Campaign;
use Modules\Campaign\Entities\CampaignMaillist;

class CampaignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates sample marketing campaigns for testing and development.
     * Each campaign components:
     * - Unique identifier (uid)
     * - Campaign type (regular, automated, etc.)
     * - Email content (HTML and plain text)
     * - Sender information
     * - Tracking backups
     * - Association with mailing list
     */
    public function run(): void
    {
        // Get the first mailing list to associate campaigns with
        $maillist = CampaignMaillist::first();

        if (! $maillist) {
            $this->command->warn('⚠️  No mailing list found. Please run CampaignMaillistSeeder first.');

            return;
        }

        $campaigns = [
            [
                'uid' => Str::ulid(),
                'type' => 'regular',
                'title' => 'Q1 Product Launch Campaign',
                'subject' => 'Introducing Our New Q1 Products',
                'from_email' => $maillist->from_email,
                'from_name' => $maillist->from_name,
                'reply_to' => $maillist->from_email,
                'status' => 'draft',
                'track_open' => 1,
                'track_click' => 1,
                'sign_dkim' => 0,
                'plain' => $this->getPlainTextProductLaunch(),
                'template_source' => $this->getHtmlTemplateProductLaunch(),
                'preheader' => 'Discover our exciting new product lineup',
                'default_maillist_id' => $maillist->id,
            ],
            [
                'uid' => Str::ulid(),
                'type' => 'regular',
                'title' => 'Spring Sale Campaign',
                'subject' => 'Spring Into Savings - Up to 40% Off',
                'from_email' => $maillist->from_email,
                'from_name' => $maillist->from_name,
                'reply_to' => $maillist->from_email,
                'status' => 'draft',
                'track_open' => 1,
                'track_click' => 1,
                'sign_dkim' => 0,
                'plain' => $this->getPlainTextSpringSale(),
                'template_source' => $this->getHtmlTemplateSpringSale(),
                'preheader' => 'Spring sale is now live - save big',
                'default_maillist_id' => $maillist->id,
            ],
            [
                'uid' => Str::ulid(),
                'type' => 'regular',
                'title' => 'Customer Testimonial Campaign',
                'subject' => 'See What Our Happy Customers Are Saying',
                'from_email' => $maillist->from_email,
                'from_name' => $maillist->from_name,
                'reply_to' => $maillist->from_email,
                'status' => 'draft',
                'track_open' => 1,
                'track_click' => 1,
                'sign_dkim' => 0,
                'plain' => $this->getPlainTextTestimonials(),
                'template_source' => $this->getHtmlTemplateTestimonials(),
                'preheader' => 'Real success stories from real customers',
                'default_maillist_id' => $maillist->id,
            ],
            [
                'uid' => Str::ulid(),
                'type' => 'regular',
                'title' => 'Webinar Invitation Campaign',
                'subject' => 'Join Us for an Exclusive Webinar',
                'from_email' => $maillist->from_email,
                'from_name' => $maillist->from_name,
                'reply_to' => $maillist->from_email,
                'status' => 'draft',
                'track_open' => 1,
                'track_click' => 1,
                'sign_dkim' => 0,
                'plain' => $this->getPlainTextWebinar(),
                'template_source' => $this->getHtmlTemplateWebinar(),
                'preheader' => 'Learn industry secrets from experts',
                'default_maillist_id' => $maillist->id,
            ],
        ];

        foreach ($campaigns as $campaign) {
            Campaign::firstOrCreate(
                ['title' => $campaign['title']],
                $campaign
            );
        }

        $this->command->info('✅ Campaigns seeded successfully ('.count($campaigns).' campaigns created)');
    }

    /**
     * Get product launch HTML template.
     */
    private function getHtmlTemplateProductLaunch(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 0; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 32px; font-weight: bold; }
        .product-section { padding: 40px 20px; background-color: #fff; }
        .product-item { border-left: 4px solid #667eea; padding: 20px; margin: 20px 0; background-color: #f8f9ff; }
        .product-item h3 { margin-top: 0; color: #667eea; }
        .cta-button { display: inline-block; padding: 12px 30px; background-color: #667eea; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; background-color: #f5f5f5; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>New Products Are Here!</h1>
            <p>Q1 Product Launch 2024</p>
        </div>
        <div class="product-section">
            <p>Hello there,</p>
            <p>We're thrilled to announce the launch of our new product line for Q1 2024. Our team has been working tirelessly to bring you innovative solutions that address your everyday needs.</p>

            <div class="product-item">
                <h3>Premium Edition</h3>
                <p>Our flagship product featuring advanced capabilities and premium features. Perfect for professionals who demand the best.</p>
            </div>

            <div class="product-item">
                <h3>Starter Edition</h3>
                <p>A more affordable option that doesn't compromise on quality. Great for those just getting started.</p>
            </div>

            <div class="product-item">
                <h3>Enterprise Solution</h3>
                <p>Designed for large organizations requiring custom solutions and dedicated support.</p>
            </div>

            <p style="text-align: center;">
                <a href="#" class="cta-button">Explore All Products</a>
            </p>

            <p>Limited time offer: Get 20% off your first purchase with code LAUNCH20</p>
            <p>Best regards,<br>Product Team</p>
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
     * Get spring sale HTML template.
     */
    private function getHtmlTemplateSpringSale(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; }
        .container { max-width: 600px; margin: 0 auto; }
        .banner { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 50px 20px; text-align: center; }
        .banner h1 { margin: 0; font-size: 36px; font-weight: bold; }
        .content { padding: 30px 20px; background-color: #f9f9f9; }
        .discount-box { background-color: #fff; border: 3px solid #38ef7d; padding: 20px; text-align: center; margin: 20px 0; border-radius: 10px; }
        .discount-box .percent { font-size: 48px; font-weight: bold; color: #11998e; }
        .categories { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 20px 0; }
        .category-box { background-color: #fff; padding: 15px; text-align: center; border-radius: 5px; }
        .cta-button { display: inline-block; padding: 15px 40px; background-color: #11998e; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px; }
        .footer { text-align: center; padding: 20px; background-color: #f0f0f0; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="banner">
            <h1>Spring Into Savings</h1>
            <p style="font-size: 24px; margin: 10px 0;">UP TO 40% OFF</p>
        </div>
        <div class="content">
            <p>Dear Valued Customer,</p>
            <p>Spring has sprung, and so have our amazing savings! Join us for our biggest seasonal sale of the year.</p>

            <div class="discount-box">
                <p>Use Code: <strong style="color: #11998e; font-size: 18px;">SPRING40</strong></p>
                <p style="margin: 0;">Get up to 40% off on selected items</p>
            </div>

            <div class="categories">
                <div class="category-box">
                    <h3 style="margin: 0 0 10px 0; color: #11998e;">Fashion</h3>
                    <p style="margin: 0; font-size: 14px;">35% OFF</p>
                </div>
                <div class="category-box">
                    <h3 style="margin: 0 0 10px 0; color: #11998e;">Home</h3>
                    <p style="margin: 0; font-size: 14px;">30% OFF</p>
                </div>
                <div class="category-box">
                    <h3 style="margin: 0 0 10px 0; color: #11998e;">Electronics</h3>
                    <p style="margin: 0; font-size: 14px;">25% OFF</p>
                </div>
                <div class="category-box">
                    <h3 style="margin: 0 0 10px 0; color: #11998e;">Outdoors</h3>
                    <p style="margin: 0; font-size: 14px;">40% OFF</p>
                </div>
            </div>

            <p style="text-align: center;">
                <a href="#" class="cta-button">Shop Spring Sale</a>
            </p>

            <p style="color: #666; font-size: 12px;">Offer expires on the last day of spring. Some exclusions apply.</p>
            <p>Best regards,<br>Sales Team</p>
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
     * Get customer testimonials HTML template.
     */
    private function getHtmlTemplateTestimonials(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Georgia, serif; line-height: 1.8; color: #333; }
        .container { max-width: 600px; margin: 0 auto; }
        .header { background-color: #f5f5f5; padding: 30px 20px; text-align: center; border-bottom: 4px solid #8b7355; }
        .header h1 { margin: 0; color: #8b7355; font-size: 28px; }
        .testimonial { padding: 25px 20px; border-left: 4px solid #d4a574; background-color: #fef9f5; margin: 20px 0; }
        .testimonial p { margin: 0 0 10px 0; font-style: italic; color: #555; }
        .author { font-weight: bold; color: #8b7355; margin-top: 10px; }
        .stars { color: #ffc107; font-size: 18px; margin: 10px 0; }
        .cta-section { padding: 30px 20px; text-align: center; background-color: #fff9f5; }
        .cta-button { display: inline-block; padding: 12px 30px; background-color: #8b7355; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .footer { text-align: center; padding: 20px; background-color: #f5f5f5; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>What Our Customers Say</h1>
            <p>Real reviews from real people who love our products</p>
        </div>

        <div class="testimonial">
            <div class="stars">★★★★★</div>
            <p>"This product has completely transformed how I work. I can't imagine going back to my old way of doing things. Highly recommended!"</p>
            <div class="author">- Sarah Mitchell, Marketing Manager</div>
        </div>

        <div class="testimonial">
            <div class="stars">★★★★★</div>
            <p>"The customer service is exceptional. They really went above and beyond to make sure I was satisfied with my purchase. Thank you!"</p>
            <div class="author">- James Chen, Entrepreneur</div>
        </div>

        <div class="testimonial">
            <div class="stars">★★★★★</div>
            <p>"Best investment I've made for my business. The ROI was immediate and continues to grow. This is a game-changer."</p>
            <div class="author">- Michelle Rodriguez, Business Owner</div>
        </div>

        <div class="cta-section">
            <p style="margin-bottom: 20px;">Join thousands of satisfied customers today</p>
            <a href="#" class="cta-button">See All Products</a>
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
     * Get webinar invitation HTML template.
     */
    private function getHtmlTemplateWebinar(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 28px; }
        .event-box { background-color: #f0f4ff; border: 2px solid #667eea; padding: 20px; margin: 20px; border-radius: 5px; }
        .event-box h3 { color: #667eea; margin-top: 0; }
        .event-detail { margin: 10px 0; }
        .event-detail .label { font-weight: bold; color: #667eea; }
        .speaker-section { padding: 20px; background-color: #fff; }
        .speaker { background-color: #f8f9ff; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .speaker h4 { margin: 0 0 5px 0; color: #667eea; }
        .cta-button { display: inline-block; padding: 15px 40px; background-color: #667eea; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px; }
        .footer { text-align: center; padding: 20px; background-color: #f5f5f5; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>You're Invited!</h1>
            <p>Exclusive Webinar for Our Community</p>
        </div>

        <div class="event-box">
            <h3>Webinar Topic: Mastering Digital Strategy in 2024</h3>
            <div class="event-detail">
                <span class="label">📅 Date:</span> January 15, 2024
            </div>
            <div class="event-detail">
                <span class="label">⏰ Time:</span> 2:00 PM - 3:30 PM EST
            </div>
            <div class="event-detail">
                <span class="label">📍 Location:</span> Online (Zoom)
            </div>
            <div class="event-detail">
                <span class="label">💰 Cost:</span> FREE for registered members
            </div>
        </div>

        <div class="speaker-section">
            <h3>Meet Our Speakers</h3>

            <div class="speaker">
                <h4>John Anderson</h4>
                <p style="margin: 0; font-size: 14px;">Chief Strategy Officer at TechCorp</p>
            </div>

            <div class="speaker">
                <h4>Dr. Emily Watson</h4>
                <p style="margin: 0; font-size: 14px;">Digital Transformation Expert & Author</p>
            </div>

            <p style="margin-top: 20px; background-color: #f0f4ff; padding: 15px; border-radius: 5px;">
                <strong>What You'll Learn:</strong><br>
                • Latest digital strategy trends<br>
                • Real-world case studies<br>
                • Q&A with industry experts<br>
                • Exclusive resource downloads
            </p>

            <p style="text-align: center; margin: 30px 0;">
                <a href="#" class="cta-button">Register Now</a>
            </p>

            <p style="font-size: 12px; color: #666;">Limited seats available. Register early to secure your spot!</p>
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
     * Get plain text product launch email.
     */
    private function getPlainTextProductLaunch(): string
    {
        return <<<'TEXT'
NEW PRODUCTS ARE HERE!
Q1 Product Launch 2024

Hello there,

We're thrilled to announce the launch of our new product line for Q1 2024. Our team has been working tirelessly to bring you innovative solutions that address your everyday needs.

PREMIUM EDITION
Our flagship product featuring advanced capabilities and premium features. Perfect for professionals who demand the best.

STARTER EDITION
A more affordable option that doesn't compromise on quality. Great for those just getting started.

ENTERPRISE SOLUTION
Designed for large organizations requiring custom solutions and dedicated support.

LIMITED TIME OFFER:
Get 20% off your first purchase with code LAUNCH20

Explore all products and start your journey with us today.

Best regards,
Product Team

© 2024 Our Company. All rights reserved.
TEXT;
    }

    /**
     * Get plain text spring sale email.
     */
    private function getPlainTextSpringSale(): string
    {
        return <<<'TEXT'
SPRING INTO SAVINGS
UP TO 40% OFF

Dear Valued Customer,

Spring has sprung, and so have our amazing savings! Join us for our biggest seasonal sale of the year.

USE CODE: SPRING40

Category Discounts:
- Fashion: 35% OFF
- Home: 30% OFF
- Electronics: 25% OFF
- Outdoors: 40% OFF

Shop our spring sale and save big on everything you need.

Offer expires on the last day of spring. Some exclusions apply.

Best regards,
Sales Team

© 2024 Our Company. All rights reserved.
TEXT;
    }

    /**
     * Get plain text testimonials email.
     */
    private function getPlainTextTestimonials(): string
    {
        return <<<'TEXT'
WHAT OUR CUSTOMERS SAY
Real reviews from real people who love our products

★★★★★
"This product has completely transformed how I work. I can't imagine going back to my old way of doing things. Highly recommended!"
- Sarah Mitchell, Marketing Manager

★★★★★
"The customer service is exceptional. They really went above and beyond to make sure I was satisfied with my purchase. Thank you!"
- James Chen, Entrepreneur

★★★★★
"Best investment I've made for my business. The ROI was immediate and continues to grow. This is a game-changer."
- Michelle Rodriguez, Business Owner

Join thousands of satisfied customers today. See all our products and start your journey with us.

© 2024 Our Company. All rights reserved.
TEXT;
    }

    /**
     * Get plain text webinar email.
     */
    private function getPlainTextWebinar(): string
    {
        return <<<'TEXT'
YOU'RE INVITED!
Exclusive Webinar for Our Community

WEBINAR: Mastering Digital Strategy in 2024

📅 Date: January 15, 2024
⏰ Time: 2:00 PM - 3:30 PM EST
📍 Location: Online (Zoom)
💰 Cost: FREE for registered members

MEET OUR SPEAKERS:
John Anderson - Chief Strategy Officer at TechCorp
Dr. Emily Watson - Digital Transformation Expert & Author

WHAT YOU'LL LEARN:
• Latest digital strategy trends
• Real-world case studies
• Q&A with industry experts
• Exclusive resource downloads

Limited seats available. Register early to secure your spot!

© 2024 Our Company. All rights reserved.
TEXT;
    }
}
