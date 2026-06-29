<?php

namespace Modules\Reviews\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReviewsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ReviewsPermissionsSeeder::class,
        ]);

        // Seed default reply templates via raw SQL
        $this->seedDefaultTemplates();

        // Seed auto-suggestions after templates
        $this->call([
            ReviewAutoSuggestionsSeeder::class,
        ]);
    }

    private function seedDefaultTemplates(): void
    {
        $templates = [
            [
                'name' => 'Agradecimiento - Reseña positiva',
                'category' => 'positive',
                'body' => 'Hola {reviewer_name}, ¡muchas gracias por tu reseña! En {location_name} valoramos mucho tu opinión. Esperamos verte pronto.',
                'is_active' => 1,
            ],
            [
                'name' => 'Disculpa - Reseña negativa',
                'category' => 'negative',
                'body' => 'Estimado/a {reviewer_name}, lamentamos mucho tu experiencia. En {location_name} nos tomamos muy en serio tus comentarios. ¿Podrías contactarnos directamente para resolver esta situación?',
                'is_active' => 1,
            ],
            [
                'name' => 'Respuesta neutral',
                'category' => 'neutral',
                'body' => 'Gracias {reviewer_name} por tu reseña. Tus comentarios nos ayudan a mejorar cada día en {location_name}.',
                'is_active' => 1,
            ],
            [
                'name' => 'Respuesta general',
                'category' => 'general',
                'body' => 'Agradecemos tu tiempo al dejar una reseña en {location_name}. ¡Esperamos verte pronto!',
                'is_active' => 1,
            ],
        ];

        foreach ($templates as $template) {
            DB::table('review_reply_templates')->updateOrInsert(
                ['name' => $template['name']],
                [
                    ...$template,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
