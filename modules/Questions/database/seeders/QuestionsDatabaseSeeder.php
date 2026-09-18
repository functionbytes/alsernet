<?php

namespace Modules\Questions\Database\Seeders;

use Illuminate\Database\Seeder;

class QuestionsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(QuestionsPermissionsSeeder::class);

        // El layout va antes: las plantillas lo buscan por alias.
        $this->call(QuestionsMailerLayoutSeeder::class);
        $this->call(QuestionsEmailTemplatesSeeder::class);
    }
}
