<?php

namespace Modules\Helpdesk\Services\Campaigns;

use Modules\Helpdesk\Models\Campaigns\Broadcast;
use Modules\Helpdesk\Models\Customer;

class BroadcastService
{
    /**
     * Preview how many customers match the given segment filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function previewSegment(array $filters): int
    {
        return $this->buildCustomerQuery($filters)->count();
    }

    /**
     * INCOMPLETO — no conectado a ningún flujo real: referenciaba
     * Modules\Helpdesk\Jobs\Campaigns\SendBroadcastMessageJob, una clase que
     * nunca llegó a existir en el árbol, y este servicio no tiene ningún
     * caller (verificado por grep). Se deja la excepción explícita para que
     * no pueda invocarse por accidente y dejar broadcasts a medio procesar
     * (recipients creados en BD pero sin job real que los despache).
     *
     * Si se retoma esta feature, el envío real ya vive en SendBroadcastJob /
     * SendBroadcastChunkJob (modules/Helpdesk/app/Jobs/).
     */
    public function dispatchBroadcast(Broadcast $broadcast): void
    {
        throw new \RuntimeException('BroadcastService::dispatchBroadcast() no está conectado; usar SendBroadcastJob/SendBroadcastChunkJob');
    }

    /**
     * Build a query for customers matching the given segment filters.
     *
     * @param  array<string, mixed>  $filters
     */
    private function buildCustomerQuery(array $filters)
    {
        $query = Customer::query()->whereNull('deleted_at');

        // Filter by channel availability
        if ($channel = $filters['channel'] ?? null) {
            $query->when($channel === 'whatsapp', fn ($q) => $q->whereNotNull('whatsapp_phone'))
                ->when($channel === 'facebook', fn ($q) => $q->whereNotNull('facebook_psid'))
                ->when($channel === 'instagram', fn ($q) => $q->whereNotNull('instagram_id'))
                ->when($channel === 'email', fn ($q) => $q->whereNotNull('email'));
        }

        // Filter by tag (customers who had a conversation tagged with this)
        if ($tag = $filters['tag'] ?? null) {
            $query->whereHas('conversations', function ($q) use ($tag) {
                $q->whereHas('conversationTags', function ($q2) use ($tag) {
                    $q2->where('slug', $tag);
                });
            });
        }

        // Filter by last contact after a given date
        if ($lastContactAfter = $filters['last_contact_after'] ?? null) {
            $query->where('last_seen_at', '>=', $lastContactAfter);
        }

        // Filter by last contact before a given date
        if ($lastContactBefore = $filters['last_contact_before'] ?? null) {
            $query->where('last_seen_at', '<=', $lastContactBefore);
        }

        // Exclude banned customers
        $query->whereNull('banned_at');

        return $query;
    }
}
