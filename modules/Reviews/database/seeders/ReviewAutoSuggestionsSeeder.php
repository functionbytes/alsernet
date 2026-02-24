<?php

namespace Modules\Reviews\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReviewAutoSuggestionsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAutoSuggestions();
    }

    private function seedAutoSuggestions(): void
    {
        $positiveTemplateId = DB::table('review_reply_templates')
            ->where('category', 'positive')
            ->value('id');

        $negativeTemplateId = DB::table('review_reply_templates')
            ->where('category', 'negative')
            ->value('id');

        $neutralTemplateId = DB::table('review_reply_templates')
            ->where('category', 'neutral')
            ->value('id');

        $generalTemplateId = DB::table('review_reply_templates')
            ->where('category', 'general')
            ->value('id');

        $suggestions = [
            [
                'trigger_keywords' => json_encode(['excelente', 'increíble', 'perfecto', 'genial', 'fantástico']),
                'rating_range' => '4-5',
                'suggested_template_id' => $positiveTemplateId,
                'priority' => 10,
                'is_active' => 1,
            ],
            [
                'trigger_keywords' => json_encode(['gracias', 'encantado', 'feliz', 'satisfecho', 'contento']),
                'rating_range' => '4-5',
                'suggested_template_id' => $positiveTemplateId,
                'priority' => 9,
                'is_active' => 1,
            ],
            [
                'trigger_keywords' => json_encode(['recomiendo', 'profesional', 'calidad', 'mejor', 'volveré']),
                'rating_range' => '5',
                'suggested_template_id' => $positiveTemplateId,
                'priority' => 10,
                'is_active' => 1,
            ],
            [
                'trigger_keywords' => json_encode(['malo', 'terrible', 'pésimo', 'horrible', 'decepcionante']),
                'rating_range' => '1-2',
                'suggested_template_id' => $negativeTemplateId,
                'priority' => 10,
                'is_active' => 1,
            ],
            [
                'trigger_keywords' => json_encode(['decepcionado', 'frustrado', 'molesto', 'insatisfecho']),
                'rating_range' => '1-2',
                'suggested_template_id' => $negativeTemplateId,
                'priority' => 9,
                'is_active' => 1,
            ],
            [
                'trigger_keywords' => json_encode(['lento', 'tardado', 'demora', 'espera', 'tiempo']),
                'rating_range' => '1-2',
                'suggested_template_id' => $negativeTemplateId,
                'priority' => 8,
                'is_active' => 1,
            ],
            [
                'trigger_keywords' => json_encode(['rudo', 'grosero', 'mala atención', 'mal servicio', 'descortés']),
                'rating_range' => '1-2',
                'suggested_template_id' => $negativeTemplateId,
                'priority' => 9,
                'is_active' => 1,
            ],
            [
                'trigger_keywords' => json_encode(['normal', 'regular', 'aceptable', 'decente', 'ok']),
                'rating_range' => '3',
                'suggested_template_id' => $neutralTemplateId,
                'priority' => 7,
                'is_active' => 1,
            ],
            [
                'trigger_keywords' => json_encode(['bien', 'correcto', 'adecuado', 'promedio', 'estándar']),
                'rating_range' => '3',
                'suggested_template_id' => $neutralTemplateId,
                'priority' => 6,
                'is_active' => 1,
            ],
            [
                'trigger_keywords' => json_encode([]),
                'rating_range' => '4-5',
                'suggested_template_id' => $generalTemplateId,
                'priority' => 1,
                'is_active' => 1,
            ],
        ];

        foreach ($suggestions as $suggestion) {
            DB::table('review_auto_suggestions')->updateOrInsert(
                [
                    'trigger_keywords' => $suggestion['trigger_keywords'],
                    'rating_range' => $suggestion['rating_range'],
                ],
                [
                    ...$suggestion,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
