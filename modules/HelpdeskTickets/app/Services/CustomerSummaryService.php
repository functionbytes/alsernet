<?php

namespace Modules\HelpdeskTickets\Services;

use Illuminate\Support\Facades\Route;
use Modules\Helpdesk\Models\Company;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskContacts\Services\ContactAggregatorService;
use Nwidart\Modules\Facades\Module;

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
            'integrations' => $stats['integrations'] ?? [],
            'url_c360' => Route::has('contacts.show') ? route('contacts.show', $customer->id) : null,
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
        } catch (\Throwable) {
            return [];
        }

        return [
            'tickets_count' => $resumen['stats']['ticketsCount'] ?? null,
            'avg_csat' => $resumen['stats']['avgCsat'] ?? null,
            'integrations' => collect($resumen['integrations'] ?? [])->filter(fn (array $i) => $i['connected'])->values()->all(),
        ];
    }
}
