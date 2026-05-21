<?php

namespace Modules\HelpdeskErp\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Nwidart\Modules\Facades\Module;

class CustomerTimelineService
{
    public function __construct(
        private readonly ErpContextService $erpService,
    ) {}

    /**
     * Returns chronological events for a customer (ERP + PS + Helpdesk).
     *
     * @return array<int, array{type: string, source: string, date: string, title: string, data: array<string, mixed>}>
     */
    public function getTimeline(string $email, int $limit = 50): array
    {
        return array_slice($this->collectEvents($email), 0, $limit);
    }

    public function forgetCache(string $email): void
    {
        Cache::forget($this->cacheKey($email));
    }

    /**
     * Aggregates and caches the full (unsliced) event list for a customer.
     *
     * @return array<int, array<string, mixed>>
     */
    private function collectEvents(string $email): array
    {
        $ttl = (int) config('helpdeskErp.timeline_cache_ttl', 120);

        if ($ttl <= 0) {
            return $this->buildEvents($email);
        }

        return Cache::remember($this->cacheKey($email), $ttl, fn () => $this->buildEvents($email));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildEvents(string $email): array
    {
        $erp = $this->erpService->getCustomerContext($email);

        $events = array_merge(
            $this->erpOrderEvents($erp),
            $this->erpInvoiceEvents($erp),
            $this->psEvents($email),
            $this->collectHelpdeskEvents($email),
        );

        usort($events, fn ($a, $b) => strtotime($b['date']) - strtotime($a['date']));

        return $events;
    }

    private function cacheKey(string $email): string
    {
        return 'erp_timeline_'.md5(strtolower(trim($email)));
    }

    /**
     * Resolve PS events defensively — only if the module is available and loaded.
     *
     * @return array<int, array<string, mixed>>
     */
    private function psEvents(string $email): array
    {
        $psClass = 'Modules\HelpdeskPrestashop\Services\PrestashopContextService';

        if (! Module::has('HelpdeskPrestashop') || ! class_exists($psClass)) {
            return [];
        }

        try {
            /** @var object $psService */
            $psService = app($psClass);
            $ps = $psService->getCustomerContext($email);

            return array_merge(
                $this->psOrderEvents($ps),
                $this->psCartEvents($ps),
            );
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param  array<string, mixed>  $erp
     * @return array<int, array<string, mixed>>
     */
    private function erpOrderEvents(array $erp): array
    {
        $events = [];

        foreach (($erp['orders'] ?? []) as $o) {
            if (empty($o['date'])) {
                continue;
            }

            $events[] = [
                'type' => 'erp_order',
                'source' => 'erp',
                'date' => (string) $o['date'],
                'title' => 'Pedido ERP '.($o['number'] ?? '#'),
                'data' => $o,
            ];
        }

        return $events;
    }

    /**
     * @param  array<string, mixed>  $erp
     * @return array<int, array<string, mixed>>
     */
    private function erpInvoiceEvents(array $erp): array
    {
        $events = [];

        foreach (($erp['invoices'] ?? []) as $i) {
            if (empty($i['date'])) {
                continue;
            }

            $events[] = [
                'type' => 'erp_invoice',
                'source' => 'erp',
                'date' => (string) $i['date'],
                'title' => 'Factura '.($i['number'] ?? '#'),
                'data' => $i,
            ];
        }

        return $events;
    }

    /**
     * @param  array<string, mixed>  $ps
     * @return array<int, array<string, mixed>>
     */
    private function psOrderEvents(array $ps): array
    {
        $events = [];

        foreach (($ps['orders'] ?? []) as $o) {
            $date = $o['placed_at'] ?? $o['date_add'] ?? null;

            if (! $date) {
                continue;
            }

            $events[] = [
                'type' => 'ps_order',
                'source' => 'prestashop',
                'date' => (string) $date,
                'title' => 'Pedido PS '.($o['reference'] ?? ''),
                'data' => $o,
            ];
        }

        return $events;
    }

    /**
     * @param  array<string, mixed>  $ps
     * @return array<int, array<string, mixed>>
     */
    private function psCartEvents(array $ps): array
    {
        $events = [];

        foreach (($ps['carts'] ?? []) as $c) {
            $date = $c['updated_at'] ?? $c['date_upd'] ?? null;

            if (! $date) {
                continue;
            }

            $events[] = [
                'type' => 'ps_cart',
                'source' => 'prestashop',
                'date' => (string) $date,
                'title' => 'Carrito abandonado',
                'data' => $c,
            ];
        }

        return $events;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function collectHelpdeskEvents(string $email): array
    {
        $events = [];

        try {
            if (! Schema::hasTable('helpdesk_conversations')) {
                return $events;
            }

            $candidates = array_filter(
                ['email', 'from_email'],
                fn ($col) => Schema::hasColumn('helpdesk_conversations', $col)
            );

            if (empty($candidates)) {
                return $events;
            }

            $rows = DB::table('helpdesk_conversations')
                ->where(function ($q) use ($email, $candidates) {
                    foreach ($candidates as $col) {
                        $q->orWhere($col, $email);
                    }
                })
                ->orderByDesc('created_at')
                ->limit(20)
                ->get();

            foreach ($rows as $r) {
                $events[] = [
                    'type' => 'helpdesk_conversation',
                    'source' => 'helpdesk',
                    'date' => (string) ($r->created_at ?? ''),
                    'title' => 'Conversación: '.substr((string) ($r->subject ?? $r->title ?? 'Sin asunto'), 0, 80),
                    'data' => (array) $r,
                ];
            }
        } catch (\Throwable) {
            // best effort — helpdesk tables may not exist
        }

        return $events;
    }
}
