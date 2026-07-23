<?php

namespace Modules\HelpdeskTickets\Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            HelpdeskTicketsPermissionsSeeder::class,
            HelpdeskTicketCategorySeeder::class,
            HelpdeskTicketStatusSeeder::class,
            HelpdeskTicketSlaPolicySeeder::class,
            PrioritiesSeeder::class,
            StatusesSeeder::class,
            CategoriesSeeder::class,
            TicketViewSeeder::class,
            HelpdeskTicketsEmailTemplatesSeeder::class,
        ]);
    }
}
