<?php

namespace Modules\HelpdeskContacts\Services;

use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\Customer;

class ContactMergeService
{
    /**
     * Merge $loser into $winner on the helpdesk connection.
     *
     * All related records (conversations, tickets, chats) are re-pointed to
     * the winner. Integration IDs present on the loser but missing on the
     * winner are copied over. The loser is then soft-deleted (or banned if
     * the model has no SoftDeletes).
     */
    public function merge(Customer $winner, Customer $loser): void
    {
        DB::connection('helpdesk')->transaction(function () use ($winner, $loser) {
            $this->transferConversations($winner, $loser);
            $this->transferTickets($winner, $loser);
            $this->transferChats($winner, $loser);
            $this->copyMissingIntegrationIds($winner, $loser);
            $this->refreshConversationCount($winner);
            $this->deleteLoser($loser);
        });
    }

    private function transferConversations(Customer $winner, Customer $loser): void
    {
        DB::connection('helpdesk')
            ->table('helpdesk_conversations')
            ->where('customer_id', $loser->id)
            ->update(['customer_id' => $winner->id]);
    }

    private function transferTickets(Customer $winner, Customer $loser): void
    {
        $class = 'Modules\\HelpdeskTickets\\Models\\Ticket';

        if (! class_exists($class)) {
            return;
        }

        $table = (new $class)->getTable();

        DB::connection('helpdesk')
            ->table($table)
            ->where('customer_id', $loser->id)
            ->update(['customer_id' => $winner->id]);
    }

    private function transferChats(Customer $winner, Customer $loser): void
    {
        $class = 'Modules\\HelpdeskLivechat\\Models\\WidgetSession';

        if (! class_exists($class)) {
            return;
        }

        $table = (new $class)->getTable();

        DB::connection('helpdesk')
            ->table($table)
            ->where('customer_id', $loser->id)
            ->update(['customer_id' => $winner->id]);
    }

    /**
     * Copy integration IDs from the loser to the winner when the winner does
     * not already have a value for that field.
     */
    private function copyMissingIntegrationIds(Customer $winner, Customer $loser): void
    {
        $fields = ['erp_customer_id', 'facebook_psid', 'instagram_id', 'whatsapp_phone'];

        $updates = [];
        foreach ($fields as $field) {
            if (empty($winner->$field) && ! empty($loser->$field)) {
                $updates[$field] = $loser->$field;
            }
        }

        if (! empty($updates)) {
            $winner->update($updates);
        }

        // Transfer external ID links not already present on the winner.
        foreach ($loser->externalIds as $link) {
            $winner->externalIds()->firstOrCreate(
                ['platform' => $link->platform],
                ['external_id' => $link->external_id, 'metadata' => $link->metadata]
            );
        }
    }

    private function refreshConversationCount(Customer $winner): void
    {
        $count = DB::connection('helpdesk')
            ->table('helpdesk_conversations')
            ->where('customer_id', $winner->id)
            ->whereNull('deleted_at')
            ->count();

        $winner->update(['total_conversations' => $count]);
    }

    private function deleteLoser(Customer $loser): void
    {
        // Customer uses SoftDeletes (confirmed in model).
        $loser->delete();
    }
}
