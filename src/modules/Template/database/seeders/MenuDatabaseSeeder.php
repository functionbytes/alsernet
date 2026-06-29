<?php

namespace Modules\Template\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Template\Models\Menu;
use Modules\Template\Models\MenuItem;

class MenuDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Header Menu
        $headerMenu = Menu::create([
            'name' => 'Header Menu',
            'slug' => 'header-menu',
            'location' => 'header',
            'status' => true,
        ]);

        // Add menu items to Header Menu
        MenuItem::create([
            'menu_id' => $headerMenu->id,
            'title' => 'Home',
            'url' => '/',
            'target' => '_self',
            'order' => 0,
            'type' => 'custom',
        ]);

        $aboutItem = MenuItem::create([
            'menu_id' => $headerMenu->id,
            'title' => 'About',
            'url' => '/about',
            'target' => '_self',
            'order' => 1,
            'type' => 'custom',
        ]);

        // Add child items to About
        MenuItem::create([
            'menu_id' => $headerMenu->id,
            'parent_id' => $aboutItem->id,
            'title' => 'Our Team',
            'url' => '/about/team',
            'target' => '_self',
            'order' => 0,
            'type' => 'custom',
        ]);

        MenuItem::create([
            'menu_id' => $headerMenu->id,
            'parent_id' => $aboutItem->id,
            'title' => 'Our Mission',
            'url' => '/about/mission',
            'target' => '_self',
            'order' => 1,
            'type' => 'custom',
        ]);

        MenuItem::create([
            'menu_id' => $headerMenu->id,
            'title' => 'Services',
            'url' => '/services',
            'target' => '_self',
            'order' => 2,
            'type' => 'custom',
        ]);

        MenuItem::create([
            'menu_id' => $headerMenu->id,
            'title' => 'Contact',
            'url' => '/contact',
            'target' => '_self',
            'order' => 3,
            'type' => 'custom',
        ]);

        // Create Footer Menu
        $footerMenu = Menu::create([
            'name' => 'Footer Menu',
            'slug' => 'footer-menu',
            'location' => 'footer',
            'status' => true,
        ]);

        // Add menu items to Footer Menu
        MenuItem::create([
            'menu_id' => $footerMenu->id,
            'title' => 'Privacy Policy',
            'url' => '/privacy',
            'target' => '_self',
            'order' => 0,
            'type' => 'custom',
        ]);

        MenuItem::create([
            'menu_id' => $footerMenu->id,
            'title' => 'Terms of Service',
            'url' => '/terms',
            'target' => '_self',
            'order' => 1,
            'type' => 'custom',
        ]);

        MenuItem::create([
            'menu_id' => $footerMenu->id,
            'title' => 'Sitemap',
            'url' => '/sitemap',
            'target' => '_self',
            'order' => 2,
            'type' => 'custom',
        ]);
    }
}
