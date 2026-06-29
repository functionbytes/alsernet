<?php

namespace Modules\Reviews\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Reviews\Models\ReviewAutoSuggestion;
use Modules\Reviews\Models\ReviewReplyTemplate;

class ReviewAutoSuggestionSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            'positive_service' => ReviewReplyTemplate::firstOrCreate(
                ['name' => 'Gracias por valorar nuestro servicio'],
                [
                    'body' => 'Hola {reviewer_name}, muchas gracias por tu valoración positiva. Nos alegra saber que quedaste satisfecho con nuestro servicio. ¡Esperamos verte pronto!',
                    'category' => 'positive',
                    'is_active' => true,
                ]
            ),
            'positive_quality' => ReviewReplyTemplate::firstOrCreate(
                ['name' => 'Agradecimiento por calidad'],
                [
                    'body' => 'Gracias {reviewer_name} por tu excelente valoración. Nos esforzamos cada día por mantener la mejor calidad. Tu opinión nos motiva a seguir mejorando.',
                    'category' => 'positive',
                    'is_active' => true,
                ]
            ),
            'negative_service' => ReviewReplyTemplate::firstOrCreate(
                ['name' => 'Disculpa por mal servicio'],
                [
                    'body' => 'Estimado/a {reviewer_name}, lamentamos mucho que tu experiencia no haya sido satisfactoria. Tomamos muy en serio tu feedback y trabajaremos para mejorar nuestro servicio. Te agradeceríamos la oportunidad de resolverlo.',
                    'category' => 'negative',
                    'is_active' => true,
                ]
            ),
            'negative_quality' => ReviewReplyTemplate::firstOrCreate(
                ['name' => 'Disculpa por calidad'],
                [
                    'body' => 'Hola {reviewer_name}, sentimos que la calidad no haya cumplido tus expectativas. Tu opinión es muy importante para nosotros y nos ayuda a identificar áreas de mejora. ¿Podríamos contactarte para resolver esta situación?',
                    'category' => 'negative',
                    'is_active' => true,
                ]
            ),
            'neutral' => ReviewReplyTemplate::firstOrCreate(
                ['name' => 'Respuesta neutral'],
                [
                    'body' => 'Hola {reviewer_name}, gracias por compartir tu experiencia. Valoramos tus comentarios y nos gustaría saber más detalles para poder mejorar. No dudes en contactarnos.',
                    'category' => 'neutral',
                    'is_active' => true,
                ]
            ),
        ];

        $suggestions = [
            [
                'trigger_keywords' => ['servicio', 'atencion', 'amable', 'rapido', 'profesional'],
                'rating_range' => '4-5',
                'suggested_template_id' => $templates['positive_service']->id,
                'priority' => 100,
                'is_active' => true,
            ],
            [
                'trigger_keywords' => ['calidad', 'excelente', 'perfecto', 'increible', 'fantastico'],
                'rating_range' => '4-5',
                'suggested_template_id' => $templates['positive_quality']->id,
                'priority' => 90,
                'is_active' => true,
            ],
            [
                'trigger_keywords' => ['servicio', 'atencion', 'mal', 'mala', 'pesimo', 'horrible'],
                'rating_range' => '1-2',
                'suggested_template_id' => $templates['negative_service']->id,
                'priority' => 100,
                'is_active' => true,
            ],
            [
                'trigger_keywords' => ['calidad', 'deficiente', 'bajo', 'pobre', 'mediocre'],
                'rating_range' => '1-2',
                'suggested_template_id' => $templates['negative_quality']->id,
                'priority' => 90,
                'is_active' => true,
            ],
            [
                'trigger_keywords' => ['normal', 'regular', 'aceptable', 'bien'],
                'rating_range' => '3',
                'suggested_template_id' => $templates['neutral']->id,
                'priority' => 50,
                'is_active' => true,
            ],
            [
                'trigger_keywords' => [],
                'rating_range' => '4-5',
                'suggested_template_id' => $templates['positive_service']->id,
                'priority' => 10,
                'is_active' => true,
            ],
            [
                'trigger_keywords' => [],
                'rating_range' => '1-2',
                'suggested_template_id' => $templates['negative_service']->id,
                'priority' => 10,
                'is_active' => true,
            ],
            [
                'trigger_keywords' => [],
                'rating_range' => '3',
                'suggested_template_id' => $templates['neutral']->id,
                'priority' => 10,
                'is_active' => true,
            ],
        ];

        foreach ($suggestions as $suggestion) {
            ReviewAutoSuggestion::firstOrCreate(
                [
                    'suggested_template_id' => $suggestion['suggested_template_id'],
                    'rating_range' => $suggestion['rating_range'],
                ],
                $suggestion
            );
        }

        $this->command->info('Review auto-suggestions seeded successfully!');
    }
}
