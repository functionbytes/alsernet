<?php

namespace Modules\HelpdeskTickets\Services;

use Illuminate\Support\Facades\Route;
use Modules\Helpdesk\Models\Company;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskContacts\Services\ContactAggregatorService;
use Modules\HelpdeskTickets\Models\TicketMail;
use Nwidart\Modules\Facades\Module;
use Throwable;

/**
 * Bloque "Cliente" del panel lateral — usado por TicketDetailDataController
 * (ficha del ticket) y TicketMailDetailDataController (email seleccionado en
 * la bandeja). Antes vivía duplicado casi al carácter en ambos controllers
 * (`mapCustomer()`/`contactStats()`); consolidado aquí el 30-ago-2026.
 *
 * Lo básico (nombre/email/teléfono) siempre disponible; empresa/idioma/
 * tickets·CSAT/integraciones solo se rellenan cuando el dato real existe —
 * nunca se inventa un "Cliente ID" o un CSAT que no tenemos.
 */
class CustomerSummaryService
{
    /**
     * @return array<string, mixed>|null
     */
    public function summarize(?Customer $customer): ?array
    {
        if (! $customer) {
            return null;
        }

        $company = $customer->company_id ? Company::find($customer->company_id) : null;
        $stats = $this->stats($customer);

        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'company' => $company?->name,
            'customer_since_year' => $customer->created_at?->format('Y'),
            'language' => $customer->language,
            'external_id' => $customer->externalIdFor('prestashop') ?? $customer->externalIdFor('erp'),
            'is_banned' => $customer->banned_at !== null,
            'tickets_count' => $stats['tickets_count'] ?? null,
            'avg_csat' => $stats['avg_csat'] ?? null,
            // Modal 25 "Cliente 360": estadística propia de HelpdeskTickets
            // (Ticket::first_response_at), no depende de HelpdeskContacts —
            // se calcula siempre, a diferencia de tickets_count/avg_csat.
            'avg_first_response_minutes' => $this->avgFirstResponseMinutes($customer),
            'integrations' => $stats['integrations'] ?? [],
            'url_c360' => Route::has('contacts.show') ? route('contacts.show', $customer->id) : null,
        ];
    }

    /**
     * Media de minutos hasta la primera respuesta, sobre los tickets de este
     * cliente que ya la tienen (mismo cálculo — TIMESTAMPDIFF(MINUTE,
     * created_at, first_response_at) — que TicketReportsService::stats()
     * usa para la media global, aquí acotado a un cliente).
     */
    private function avgFirstResponseMinutes(Customer $customer): ?float
    {
        $avg = $customer->tickets()
            ->whereNotNull('first_response_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, first_response_at)) as avg_minutes')
            ->value('avg_minutes');

        return $avg !== null ? round((float) $avg, 1) : null;
    }

    /**
     * Modal 25 "Cliente 360": pedidos reales de PrestaShop del cliente, bajo
     * demanda -- a diferencia de tickets_count/avg_csat/avg_first_response,
     * esto llama al bridge de PrestaShop en vivo (HelpdeskPrestashop ->
     * alsernetbridge). Pagar esa latencia en CADA carga del ticket no tiene
     * sentido cuando el agente ni siquiera ha abierto el modal, así que no
     * viaja en summarize() — TicketOpsController::customerOrders() lo pide
     * aparte solo al abrirlo.
     *
     * @return array{available: bool, orders: array<int, array<string, mixed>>}
     */
    public function prestashopOrders(?Customer $customer): array
    {
        if (! $customer || ! Module::find('HelpdeskContacts')?->isEnabled() || ! class_exists(ContactAggregatorService::class)) {
            return ['available' => false, 'orders' => []];
        }

        try {
            $data = app(ContactAggregatorService::class)->prestashop($customer);
        } catch (Throwable) {
            return ['available' => false, 'orders' => []];
        }

        return [
            'available' => (bool) ($data['available'] ?? false),
            'orders' => collect($data['orders'] ?? [])->take(5)->map(fn (array $o) => [
                'reference' => $o['reference'] ?? null,
                'placed_at' => $o['placed_at'] ?? null,
                'total' => $o['totals']['total'] ?? null,
                'currency_sign' => $o['currency_sign'] ?? '€',
                'state' => $o['state']['name'] ?? null,
            ])->all(),
        ];
    }

    /**
     * Reusa Contactos 360 (HelpdeskContacts) si está instalado y activo —
     * dependencia opcional en runtime, nunca declarada en module.json. Sin
     * ese módulo, estos datos simplemente no se muestran — no se calculan
     * aquí de forma aproximada.
     *
     * @return array{tickets_count?: int, avg_csat?: float, integrations?: array<int, array<string, mixed>>}
     */
    public function stats(Customer $customer): array
    {
        if (! Module::find('HelpdeskContacts')?->isEnabled() || ! class_exists(ContactAggregatorService::class)) {
            return [];
        }

        try {
            $resumen = app(ContactAggregatorService::class)->resumen($customer);
        } catch (Throwable) {
            return [];
        }

        return [
            'tickets_count' => $resumen['stats']['ticketsCount'] ?? null,
            'avg_csat' => $resumen['stats']['avgCsat'] ?? null,
            'integrations' => collect($resumen['integrations'] ?? [])->filter(fn (array $i) => $i['connected'])->values()->all(),
        ];
    }

    /**
     * Modal 34 "Identidades del cliente".
     *
     * Un mismo contacto escribe por email, WhatsApp, redes o el formulario de
     * PrestaShop, y todo cuelga de una sola ficha. Aquí se reúnen los canales
     * que la ficha ya guarda (columnas propias + helpdesk_customer_external_ids)
     * con cuántos tickets llegó por cada uno, para poder ver de un vistazo si
     * hay una identidad que en realidad es de otra persona.
     *
     * @return array<int, array<string, mixed>>
     */
    public function identities(?Customer $customer): array
    {
        if (! $customer) {
            return [];
        }

        $identities = [];

        // Canales con columna propia en helpdesk_customers.
        foreach ([
            ['email', 'Email', 'fa-regular fa-envelope', $customer->email, true],
            ['phone', 'Teléfono', 'fa-solid fa-phone', $customer->phone, false],
            ['whatsapp', 'WhatsApp', 'fa-brands fa-whatsapp', $customer->whatsapp_phone, false],
            ['facebook', 'Facebook', 'fa-brands fa-facebook', $customer->facebook_psid, false],
            ['instagram', 'Instagram', 'fa-brands fa-instagram', $customer->instagram_id, false],
        ] as [$channel, $label, $icon, $value, $isPrimary]) {
            if (! $value) {
                continue;
            }
            $identities[] = [
                'channel' => $channel,
                'label' => $label,
                'icon' => $icon,
                'value' => $value,
                'is_primary' => $isPrimary,
                'source' => 'ficha',
            ];
        }

        // Identidades en plataformas externas (PrestaShop, ERP…).
        foreach ($customer->externalIds as $external) {
            $identities[] = [
                'channel' => $external->platform,
                'label' => ucfirst((string) $external->platform),
                'icon' => 'fa-solid fa-plug',
                'value' => $external->external_id,
                'is_primary' => false,
                'source' => 'integración',
            ];
        }

        // Direcciones distintas de la principal que YA han escrito a este
        // cliente: son las candidatas reales a unificar, y no están en
        // ninguna columna — salen del histórico de correo entrante.
        $extraAddresses = TicketMail::query()
            ->whereIn('ticket_id', $customer->tickets()->select('id'))
            ->where('direction', 'inbound')
            ->whereNotNull('from')
            ->when($customer->email, fn ($q) => $q->where('from', '!=', $customer->email))
            ->select('from')
            ->selectRaw('COUNT(*) as hits')
            ->groupBy('from')
            ->pluck('hits', 'from');

        foreach ($extraAddresses as $address => $hits) {
            $identities[] = [
                'channel' => 'email',
                'label' => 'Email secundario',
                'icon' => 'fa-regular fa-envelope',
                'value' => $address,
                'is_primary' => false,
                'source' => 'detectado en el hilo',
                'hits' => $hits,
            ];
        }

        return $identities;
    }
}
