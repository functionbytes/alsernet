<?php

namespace Modules\Helpdesk\Actions\Customers;

use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Events\CustomerMerged;
use Modules\Helpdesk\Models\Customer;

class CustomerMergeAction
{
    public function __construct(
        private readonly Customer $base,
        private readonly Customer $mergee,
    ) {}

    public function execute(): Customer
    {
        return DB::connection('helpdesk')->transaction(function () {
            $this->mergeConversations();
            $this->mergeExternalIds();
            $this->mergeCustomerInboxes();
            $this->mergeSessions();
            $this->mergeAttributes();
            $this->deleteMergee();

            event(new CustomerMerged($this->base, $this->mergee));

            return $this->base->fresh();
        });
    }

    private function mergeConversations(): void
    {
        DB::connection('helpdesk')
            ->table('helpdesk_conversations')
            ->where('customer_id', $this->mergee->id)
            ->update(['customer_id' => $this->base->id]);
    }

    private function mergeExternalIds(): void
    {
        $conn = DB::connection('helpdesk');

        // Re-assign external IDs that don't conflict on (platform, external_id):
        // los pares que el base ya tiene se BORRAN del mergee en vez de
        // re-asignarse, para no violar el índice único
        // helpdesk_cust_ext_platform_id_unique y tumbar la transacción entera
        // del merge. (Mismo patrón que ContactMergeService en HelpdeskContacts.)
        $basePairs = $conn->table('helpdesk_customer_external_ids')
            ->where('customer_id', $this->base->id)
            ->get(['platform', 'external_id'])
            ->map(fn ($row) => $row->platform.'|'.$row->external_id)
            ->all();

        $duplicateIds = $conn->table('helpdesk_customer_external_ids')
            ->where('customer_id', $this->mergee->id)
            ->get(['id', 'platform', 'external_id'])
            ->filter(fn ($row) => in_array($row->platform.'|'.$row->external_id, $basePairs, true))
            ->pluck('id')
            ->all();

        if ($duplicateIds !== []) {
            $conn->table('helpdesk_customer_external_ids')
                ->whereIn('id', $duplicateIds)
                ->delete();
        }

        $conn->table('helpdesk_customer_external_ids')
            ->where('customer_id', $this->mergee->id)
            ->update(['customer_id' => $this->base->id]);
    }

    private function mergeCustomerInboxes(): void
    {
        // For each inbox the mergee has, add it to the base if not already present.
        // If base already has that inbox, just drop the mergee's record to avoid
        // violating the unique(customer_id, inbox_id) constraint.
        $baseInboxIds = DB::connection('helpdesk')
            ->table('helpdesk_customer_inboxes')
            ->where('customer_id', $this->base->id)
            ->pluck('inbox_id')
            ->all();

        DB::connection('helpdesk')
            ->table('helpdesk_customer_inboxes')
            ->where('customer_id', $this->mergee->id)
            ->whereIn('inbox_id', $baseInboxIds)
            ->delete();

        DB::connection('helpdesk')
            ->table('helpdesk_customer_inboxes')
            ->where('customer_id', $this->mergee->id)
            ->update(['customer_id' => $this->base->id]);
    }

    private function mergeSessions(): void
    {
        DB::connection('helpdesk')
            ->table('helpdesk_customer_sessions')
            ->where('customer_id', $this->mergee->id)
            ->update(['customer_id' => $this->base->id]);
    }

    private function mergeAttributes(): void
    {
        $updates = [];

        foreach (['name', 'email', 'phone', 'avatar_url', 'country', 'state', 'city', 'language', 'timezone'] as $field) {
            if (empty($this->base->{$field}) && ! empty($this->mergee->{$field})) {
                $updates[$field] = $this->mergee->{$field};
            }
        }

        // Deep-merge custom_attributes — base values take precedence
        $baseAttrs = $this->base->custom_attributes ?? [];
        $mergeeAttrs = $this->mergee->custom_attributes ?? [];
        $merged = array_merge($mergeeAttrs, $baseAttrs);

        if ($merged !== $baseAttrs) {
            $updates['custom_attributes'] = $merged;
        }

        // total_conversations: tras mergeConversations() todas las conversaciones
        // del mergee ya apuntan al base, así que se recalcula con un COUNT real
        // (mismo criterio que ContactMergeService::refreshConversationCount) en
        // vez de sumar contadores desnormalizados que pueden estar desfasados.
        $updates['total_conversations'] = DB::connection('helpdesk')
            ->table('helpdesk_conversations')
            ->where('customer_id', $this->base->id)
            ->whereNull('deleted_at')
            ->count();

        // total_page_visits: las visitas no se re-asignan en el merge, así que
        // la suma de ambos contadores sigue siendo la mejor aproximación.
        $updates['total_page_visits'] = (int) $this->base->total_page_visits + (int) $this->mergee->total_page_visits;

        if (! empty($updates)) {
            $this->base->update($updates);
        }
    }

    private function deleteMergee(): void
    {
        $this->mergee->delete();
    }
}
