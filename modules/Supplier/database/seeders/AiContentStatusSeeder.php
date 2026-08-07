<?php

namespace Modules\Supplier\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Supplier\Models\Ai\AiContentStatus;

class AiContentStatusSeeder extends Seeder
{
    public function run(): void
    {
        // badge_class NO usa las utilities bg-*/text-bg-* de Bootstrap: este
        // theme reasigna --bs-secondary-rgb/--bs-danger-rgb por esquema de
        // color (en el esquema activo por defecto salen teal y rosa, no
        // gris ni rojo — ver modules/Theme/public/theme/css/style.css), y
        // bg-warning/bg-success sueltas no fijan color de texto (blanco
        // sobre amarillo/verde claro, casi ilegible). bg-teal/bg-orange
        // directamente no existen (badge sin fondo, invisible). Se usan en
        // su lugar las clases .badge-content-* definidas a mano en
        // content/index.blade.php, con la paleta de marca de la plataforma
        // (verde #90bb13, escala de grises, negro) en vez de colores sueltos.
        $statuses = [
            ['key' => 'pending_generation',       'label' => 'Sin generar',           'badge_class' => 'badge-content-pending',    'sort_order' => 1],
            ['key' => 'generating',               'label' => 'Generando',             'badge_class' => 'badge-content-generating', 'sort_order' => 2],
            ['key' => 'pending_validation',       'label' => 'Por validar',           'badge_class' => 'badge-content-review',     'sort_order' => 3],
            ['key' => 'in_review',                'label' => 'En revisión',           'badge_class' => 'badge-content-inreview',   'sort_order' => 4],
            ['key' => 'needs_revision',           'label' => 'Necesita revisión',     'badge_class' => 'badge-content-revision',   'sort_order' => 5],
            ['key' => 'validated',                'label' => 'Validado',              'badge_class' => 'badge-content-validated',  'sort_order' => 6],
            ['key' => 'published',                'label' => 'Publicado',             'badge_class' => 'badge-content-published',  'sort_order' => 7],
            ['key' => 'published_hidden',         'label' => 'No publicado',          'badge_class' => 'badge-content-hidden',     'sort_order' => 8],
            ['key' => 'rejected',                 'label' => 'Rechazado',             'badge_class' => 'badge-content-danger',     'sort_order' => 9],
            ['key' => 'error_insufficient_info',  'label' => 'Info insuficiente',     'badge_class' => 'badge-content-danger',     'sort_order' => 10],
            ['key' => 'error_source_unavailable', 'label' => 'Fuente no disponible',  'badge_class' => 'badge-content-danger',     'sort_order' => 11],
            ['key' => 'error_generation_failed',  'label' => 'Error de generación',   'badge_class' => 'badge-content-danger',     'sort_order' => 12],
        ];

        foreach ($statuses as $status) {
            AiContentStatus::updateOrCreate(['key' => $status['key']], $status);
        }
    }
}
