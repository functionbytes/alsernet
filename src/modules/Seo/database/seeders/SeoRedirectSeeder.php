<?php

namespace Modules\Seo\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Seo\Models\SeoRedirect;

class SeoRedirectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $redirects = [
            [
                'source_path' => '/old-about',
                'target_path' => '/about',
                'status_code' => 301,
                'is_active' => true,
            ],
            [
                'source_path' => '/old-contact',
                'target_path' => '/contact',
                'status_code' => 301,
                'is_active' => true,
            ],
            [
                'source_path' => '/blog/old-post',
                'target_path' => '/blog/new-post',
                'status_code' => 301,
                'is_active' => true,
            ],
            [
                'source_path' => '/products',
                'target_path' => '/shop',
                'status_code' => 301,
                'is_active' => true,
            ],
            [
                'source_path' => '/services/web-design',
                'target_path' => '/services/design',
                'status_code' => 301,
                'is_active' => true,
            ],
            [
                'source_path' => '/temp-promotion',
                'target_path' => '/promotions/summer-sale',
                'status_code' => 302,
                'is_active' => true,
            ],
            [
                'source_path' => '/old-pricing',
                'target_path' => '/pricing',
                'status_code' => 301,
                'is_active' => true,
            ],
            [
                'source_path' => '/news/announcement-2024',
                'target_path' => '/blog/important-announcement',
                'status_code' => 301,
                'is_active' => true,
            ],
            [
                'source_path' => '/legacy/home',
                'target_path' => '/',
                'status_code' => 301,
                'is_active' => true,
            ],
            [
                'source_path' => '/documentation',
                'target_path' => 'https://docs.example.com',
                'status_code' => 301,
                'is_active' => true,
            ],
            [
                'source_path' => '/support/faq',
                'target_path' => '/help/frequently-asked-questions',
                'status_code' => 301,
                'is_active' => true,
            ],
            [
                'source_path' => '/team',
                'target_path' => '/about/team',
                'status_code' => 301,
                'is_active' => true,
            ],
            [
                'source_path' => '/careers',
                'target_path' => '/about/careers',
                'status_code' => 301,
                'is_active' => true,
            ],
            [
                'source_path' => '/seasonal-offer',
                'target_path' => '/offers/winter-special',
                'status_code' => 302,
                'is_active' => false,
            ],
            [
                'source_path' => '/old-blog',
                'target_path' => '/blog',
                'status_code' => 301,
                'is_active' => true,
            ],
        ];

        foreach ($redirects as $redirect) {
            SeoRedirect::create($redirect);
        }

        $this->command->info('SEO redirects seeded successfully!');
    }
}
