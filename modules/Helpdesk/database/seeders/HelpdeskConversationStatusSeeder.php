<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\ConversationStatus;

class HelpdeskConversationStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds the helpdesk_conversation_statuses table with conversation lifecycle states.
     * These statuses track the state of customer conversations separate from ticket workflow.
     *
     * Conversation status workflow:
     * New (customer initiates) → Active (ongoing dialogue) → Waiting (awaiting response) → Resolved → Closed
     *
     * Additional states:
     * - Archived: Preserved but no longer active
     *
     * Depends on: None (independent)
     */
    public function run(): void
    {
        $statuses = [
            [
                'uid' => Str::ulid(),
                'name' => 'Nuevo',
                'key' => 'new',
                'color' => '#0d6efd', // primary blue
                'icon' => 'fa-duotone fa-star',
                'is_open' => true,
                'position' => 1,
                'description' => 'Conversación nueva iniciada por el cliente',
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Activo',
                'key' => 'active',
                'color' => '#0dcaf0', // info cyan
                'icon' => 'fa-duotone fa-comments',
                'is_open' => true,
                'position' => 2,
                'description' => 'Conversación en curso con diálogo activo',
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Esperando',
                'key' => 'waiting',
                'color' => '#ffc107', // warning yellow
                'icon' => 'fa-duotone fa-hourglass-half',
                'is_open' => true,
                'position' => 3,
                'description' => 'Esperando respuesta del cliente',
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Resuelto',
                'key' => 'resolved',
                'color' => '#198754', // success green
                'icon' => 'fa-duotone fa-check-circle',
                'is_open' => false,
                'position' => 4,
                'description' => 'Problema resuelto, esperando confirmación',
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Cerrado',
                'key' => 'closed',
                'color' => '#495057', // dark gray
                'icon' => 'fa-duotone fa-lock',
                'is_open' => false,
                'position' => 5,
                'description' => 'Conversación finalizada y cerrada',
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Archivado',
                'key' => 'archived',
                'color' => '#6c757d', // secondary gray
                'icon' => 'fa-duotone fa-archive',
                'is_open' => false,
                'position' => 6,
                'description' => 'Conversación archivada para referencia',
            ],
        ];

        foreach ($statuses as $status) {
            ConversationStatus::firstOrCreate(
                ['key' => $status['key']],
                $status
            );
        }

        $this->command->info('✅ Helpdesk conversation statuses seeded successfully');
    }
}
