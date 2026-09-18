<?php

namespace Modules\HelpdeskContacts\Services;

use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Actions\Customers\CustomerMergeAction;
use Modules\Helpdesk\Models\Customer;

class ContactMergeService
{
    /**
     * Merge $loser into $winner on the helpdesk connection.
     *
     * El núcleo de la fusión se delega en CustomerMergeAction (la
     * implementación endurecida del core Helpdesk): conversaciones,
     * external IDs, helpdesk_customer_inboxes, helpdesk_customer_sessions,
     * backfill de atributos (incl. total_conversations con COUNT real),
     * soft-delete del perdedor y el evento CustomerMerged. Así ambos puntos
     * de entrada (Contactos y Clientes del core) comparten una sola fuente
     * de verdad y las fusiones desde Contactos ya no dejan filas huérfanas
     * en inboxes/sesiones ni se saltan el evento.
     *
     * Antes de delegar se aplican los extras propios de Contactos:
     * tickets, chats web (widget sessions), columnas sociales
     * (facebook_psid / instagram_id / whatsapp_phone) y la política de un
     * link por plataforma en helpdesk_customer_external_ids.
     */
    public function merge(Customer $winner, Customer $loser): void
    {
        DB::connection('helpdesk')->transaction(function () use ($winner, $loser) {
            $this->transferTickets($winner, $loser);
            $this->transferChats($winner, $loser);
            $this->copyMissingIntegrationIds($winner, $loser);

            (new CustomerMergeAction($winner, $loser))->execute();
        });
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
     *
     * ERP/PrestaShop no van aqui: no son columnas directas de Customer, se
     * vinculan via la relacion externalIds.
     */
    private function copyMissingIntegrationIds(Customer $winner, Customer $loser): void
    {
        $fields = ['facebook_psid', 'instagram_id', 'whatsapp_phone'];

        $updates = [];
        foreach ($fields as $field) {
            if (empty($winner->$field) && ! empty($loser->$field)) {
                $updates[$field] = $loser->$field;
            }
        }

        if (! empty($updates)) {
            $winner->update($updates);
        }

        // Política de Contactos: un solo link por plataforma. Si el ganador
        // ya tiene un link en esa plataforma, el del perdedor se BORRA aquí
        // (gana el del ganador); los que sobreviven los re-apunta después
        // CustomerMergeAction::mergeExternalIds() respetando el unique
        // global (platform, external_id) de helpdesk_customer_external_ids.
        $winnerPlatforms = $winner->externalIds->pluck('platform');

        foreach ($loser->externalIds as $link) {
            if ($winnerPlatforms->contains($link->platform)) {
                $link->delete();
            }
        }
    }
}
