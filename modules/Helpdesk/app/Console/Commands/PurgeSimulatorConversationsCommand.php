<?php

namespace Modules\Helpdesk\Console\Commands;

use Illuminate\Console\Command;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;

/**
 * Removes conversations created by the public simulator (metadata.is_simulator),
 * so test traffic doesn't pollute the inbox, metrics or SLA reports.
 */
class PurgeSimulatorConversationsCommand extends Command
{
    protected $signature = 'helpdesk:simulator:purge
        {--days=0 : Only delete sessions older than N days (0 = all)}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Elimina las conversaciones creadas por el simulador público (metadata.is_simulator).';

    public function handle(): int
    {
        $query = Conversation::query()->where('metadata->is_simulator', true);

        $days = (int) $this->option('days');
        if ($days > 0) {
            $query->where('created_at', '<', now()->subDays($days));
        }

        $ids = (clone $query)->pluck('id');

        if ($ids->isEmpty()) {
            $this->info('No hay conversaciones de simulador para eliminar.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("¿Eliminar {$ids->count()} conversación(es) de simulador?")) {
            $this->warn('Operación cancelada.');

            return self::SUCCESS;
        }

        ConversationItem::query()->whereIn('conversation_id', $ids)->forceDelete();
        Conversation::query()->whereIn('id', $ids)->forceDelete();

        $this->info("Eliminadas {$ids->count()} conversación(es) de simulador.");

        return self::SUCCESS;
    }
}
