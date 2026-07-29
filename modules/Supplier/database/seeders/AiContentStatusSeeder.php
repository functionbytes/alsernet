<?php

namespace Modules\Supplier\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Supplier\Models\Ai\AiContentStatus;

class AiContentStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['key' => 'pending_generation',       'label' => 'Sin generar',           'badge_class' => 'bg-secondary', 'sort_order' => 1],
            ['key' => 'generating',               'label' => 'Generando',             'badge_class' => 'bg-info',      'sort_order' => 2],
            ['key' => 'pending_validation',       'label' => 'Por validar',           'badge_class' => 'bg-warning',   'sort_order' => 3],
            ['key' => 'in_review',                'label' => 'En revisión',           'badge_class' => 'bg-primary',   'sort_order' => 4],
            ['key' => 'needs_revision',           'label' => 'Necesita revisión',     'badge_class' => 'bg-orange',    'sort_order' => 5],
            ['key' => 'validated',                'label' => 'Validado',              'badge_class' => 'bg-success',   'sort_order' => 6],
            ['key' => 'published',                'label' => 'Publicado',             'badge_class' => 'bg-teal',      'sort_order' => 7],
            ['key' => 'published_hidden',         'label' => 'No publicado',          'badge_class' => 'bg-secondary', 'sort_order' => 8],
            ['key' => 'rejected',                 'label' => 'Rechazado',             'badge_class' => 'bg-danger',    'sort_order' => 9],
            ['key' => 'error_insufficient_info',  'label' => 'Info insuficiente',     'badge_class' => 'bg-danger',    'sort_order' => 10],
            ['key' => 'error_source_unavailable', 'label' => 'Fuente no disponible',  'badge_class' => 'bg-danger',    'sort_order' => 11],
            ['key' => 'error_generation_failed',  'label' => 'Error de generación',   'badge_class' => 'bg-danger',    'sort_order' => 12],
        ];

        foreach ($statuses as $status) {
            AiContentStatus::updateOrCreate(['key' => $status['key']], $status);
        }
    }
}
