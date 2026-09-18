<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Config real traída de system: prioridades, estados, etiquetas, equipos y el
 * inbox de WhatsApp Soporte (único canal que se usa en este proyecto). El
 * campo credentials de helpdesk_inboxes no se usa en runtime (WhatsApp se
 * configura por env global, ver config/helpdesk.php), por eso queda null.
 */
class HelpdeskCoreConfigSeeder extends Seeder
{
    public function run(): void
    {
        $connection = 'helpdesk';

        foreach ($this->priorities() as $row) {
            DB::connection($connection)->table('helpdesk_priorities')->updateOrInsert(
                ['slug' => $row['slug']],
                $row
            );
        }

        foreach ($this->statuses() as $row) {
            DB::connection($connection)->table('helpdesk_statuses')->updateOrInsert(
                ['slug' => $row['slug']],
                $row
            );
        }

        foreach ($this->tags() as $row) {
            DB::connection($connection)->table('helpdesk_conversation_tags')->updateOrInsert(
                ['slug' => $row['slug']],
                $row
            );
        }

        foreach ($this->groups() as $row) {
            DB::connection($connection)->table('helpdesk_groups')->updateOrInsert(
                ['key' => $row['key']],
                $row
            );
        }

        foreach ($this->inboxes() as $row) {
            DB::connection($connection)->table('helpdesk_inboxes')->updateOrInsert(
                ['uid' => $row['uid']],
                $row
            );
        }
    }

    private function priorities(): array
    {
        return [
            ['uid' => '725cf122-dd64-4317-8159-00152fd1baa4', 'name' => 'Baja', 'slug' => 'baja', 'level' => 1, 'color' => '#6C757D', 'response_time_hours' => 72, 'resolution_time_hours' => 168, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['uid' => '3074d6a3-95f3-4e4e-9716-69f80e08433d', 'name' => 'Normal', 'slug' => 'normal', 'level' => 2, 'color' => '#0D6EFD', 'response_time_hours' => 24, 'resolution_time_hours' => 72, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['uid' => '73b52bc0-1ebe-4551-9ee1-b5efafaf30f2', 'name' => 'Alta', 'slug' => 'alta', 'level' => 3, 'color' => '#FFC107', 'response_time_hours' => 8, 'resolution_time_hours' => 24, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['uid' => 'e9147c27-2588-4613-ae6f-862550c9c982', 'name' => 'Urgente', 'slug' => 'urgente', 'level' => 4, 'color' => '#FD7E14', 'response_time_hours' => 2, 'resolution_time_hours' => 8, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['uid' => 'b79ea351-5406-4213-9672-ded5a58dee41', 'name' => 'Crítico', 'slug' => 'critico', 'level' => 5, 'color' => '#DC3545', 'response_time_hours' => 1, 'resolution_time_hours' => 4, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ];
    }

    private function statuses(): array
    {
        return [
            ['uid' => 'b42620bb-0db1-4efd-ab4b-117447a18ec5', 'name' => 'New', 'slug' => 'new', 'type' => 'open', 'color' => '#6C757D', 'order' => 0, 'is_default' => 1, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['uid' => 'ecd4d04f-994d-48eb-be14-0858171efbc1', 'name' => 'Open', 'slug' => 'open', 'type' => 'open', 'color' => '#0D6EFD', 'order' => 1, 'is_default' => 0, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['uid' => 'da6927c1-60dd-4b67-8a62-7651ec7dc12d', 'name' => 'In Progress', 'slug' => 'in-progress', 'type' => 'pending', 'color' => '#0DCAF0', 'order' => 2, 'is_default' => 0, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['uid' => '77eedc96-c429-4e79-b554-8da3203b92ed', 'name' => 'On Hold', 'slug' => 'on-hold', 'type' => 'pending', 'color' => '#FFC107', 'order' => 3, 'is_default' => 0, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['uid' => '3c1f48c9-d95e-41c3-aa87-ee23ad135f2d', 'name' => 'Resolved', 'slug' => 'resolved', 'type' => 'resolved', 'color' => '#198754', 'order' => 4, 'is_default' => 0, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['uid' => 'c86068b8-e9b6-466a-b21e-e7b8a0275e41', 'name' => 'Closed', 'slug' => 'closed', 'type' => 'closed', 'color' => '#6C757D', 'order' => 5, 'is_default' => 0, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ];
    }

    private function tags(): array
    {
        return [
            ['name' => 'Urgente', 'slug' => 'urgente', 'color' => '#DC3545', 'description' => 'Requiere atención inmediata', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Bug', 'slug' => 'bug', 'color' => '#FD7E14', 'description' => 'Error o comportamiento incorrecto', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Consulta', 'slug' => 'consulta', 'color' => '#0D6EFD', 'description' => 'Pregunta o solicitud de información', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Facturación', 'slug' => 'facturacion', 'color' => '#198754', 'description' => 'Consultas de pago o factura', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Onboarding', 'slug' => 'onboarding', 'color' => '#6F42C1', 'description' => 'Proceso de incorporación de nuevos clientes', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Seguimiento', 'slug' => 'seguimiento', 'color' => '#FFC107', 'description' => 'Requiere seguimiento posterior', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Feedback', 'slug' => 'feedback', 'color' => '#0DCAF0', 'description' => 'Comentario o sugerencia del cliente', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'VIP', 'slug' => 'vip', 'color' => '#B8860B', 'description' => 'Cliente prioritario o de alto valor', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Spam', 'slug' => 'spam', 'color' => '#6C757D', 'description' => 'Mensaje no solicitado o irrelevante', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Feature Request', 'slug' => 'feature-request', 'color' => '#6610F2', 'description' => 'Solicitud de nueva funcionalidad', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'técnico-30', 'slug' => 'aut-eum-vitae', 'color' => '#33fd1c', 'description' => 'Laboriosam nihil totam odio est.', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'escalado-89', 'slug' => 'sentiment-negative-debug', 'color' => '#e12cd6', 'description' => 'Et ea illum hic reiciendis a dolorum.', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Debug', 'slug' => 'debug-6a4e969f3a326', 'color' => '#ff0000', 'description' => null, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ];
    }

    private function groups(): array
    {
        return [
            ['uid' => '01KQWW1JNERK5EJJ7FS2AHRMG1', 'name' => 'Soporte General', 'key' => 'general_support', 'description' => 'Equipo de soporte general para preguntas generales y consultas', 'tag_id' => null, 'email' => 'support@alsernet.local', 'is_active' => 1, 'position' => 1, 'assignment_mode' => 'round_robin', 'default' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['uid' => '01KQWW1JNGCCC3YZMV46B4F3AK', 'name' => 'Soporte Técnico', 'key' => 'technical_support', 'description' => 'Equipo especializado en problemas técnicos y bugs', 'tag_id' => null, 'email' => 'tech@alsernet.local', 'is_active' => 1, 'position' => 2, 'assignment_mode' => 'round_robin', 'default' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['uid' => '01KQWW1JNGCCC3YZMV46B4F3AM', 'name' => 'Soporte Facturación', 'key' => 'billing_support', 'description' => 'Equipo encargado de consultas de pagos y facturación', 'tag_id' => null, 'email' => 'billing@alsernet.local', 'is_active' => 1, 'position' => 3, 'assignment_mode' => 'load_balanced', 'default' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['uid' => '01KQWW1JNH6ZHH8RRPTZ5N1RQR', 'name' => 'Soporte Premium', 'key' => 'premium_support', 'description' => 'Equipo de soporte prioritario para clientes VIP', 'tag_id' => null, 'email' => 'premium@alsernet.local', 'is_active' => 1, 'position' => 4, 'assignment_mode' => 'load_balanced', 'default' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['uid' => '01KQWW1JNJ8Y4TEB8M4ZSQY32Q', 'name' => 'Devoluciones y Logística', 'key' => 'returns_logistics', 'description' => 'Equipo especializado en devoluciones y gestión logística', 'tag_id' => null, 'email' => 'returns@alsernet.local', 'is_active' => 1, 'position' => 5, 'assignment_mode' => 'round_robin', 'default' => 0, 'created_at' => now(), 'updated_at' => now()],
        ];
    }

    private function inboxes(): array
    {
        return [
            [
                'uid' => '48f16a47-2541-4ee8-b61e-b7bf61a1944c',
                'name' => 'WhatsApp Soporte',
                'channel_type' => 'whatsapp',
                'is_active' => 1,
                'credentials' => null,
                'color' => '#25D366',
                'icon' => 'fab fa-whatsapp',
                'timezone' => 'UTC',
                'greeting_enabled' => 0,
                'working_hours_enabled' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
    }
}
