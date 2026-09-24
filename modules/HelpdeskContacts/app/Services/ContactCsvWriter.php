<?php

namespace Modules\HelpdeskContacts\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\CustomerInsightsService;

/**
 * Escribe el CSV de "Exportar contactos": lo usan la descarga directa
 * (ContactsController::export) y el envío por email de exportaciones grandes
 * (ExportContactsJob), así ambos producen exactamente el mismo fichero.
 */
class ContactCsvWriter
{
    public function __construct(
        private readonly CustomerInsightsService $insights,
        private readonly ContactAggregatorService $aggregator,
    ) {}

    /**
     * @param  resource  $handle
     */
    public function write($handle, Builder $query, bool $includeHealth, bool $includeExternal): void
    {
        $header = [
            'ID', 'Nombre', 'Email', 'Teléfono', 'WhatsApp',
            'País', 'Última visita', 'Conversaciones', 'Verificado', 'Suspendido', 'Canales',
        ];
        if ($includeHealth) {
            $header[] = 'Salud';
            $header[] = 'Valor de vida';
        }
        if ($includeExternal) {
            $header[] = 'ID ERP';
            $header[] = 'ID PrestaShop';
            $query->with('externalIds');
        }
        fputcsv($handle, $header, ',', '"', '\\');

        $query->chunk(500, function ($customers) use ($handle, $includeHealth, $includeExternal) {
            // Batch (4 consultas agrupadas) en vez de una por fila.
            $healthScores = $includeHealth ? $this->insights->healthScoresFor($customers->pluck('id')->all()) : [];

            foreach ($customers as $customer) {
                $row = [
                    $customer->id,
                    self::csvSafe($customer->name),
                    self::csvSafe($customer->email ?? ''),
                    self::csvSafe($customer->phone ?? ''),
                    self::csvSafe($customer->whatsapp_phone ?? ''),
                    self::csvSafe($customer->country ?? ''),
                    $customer->last_seen_at?->toIso8601String() ?? '',
                    $customer->total_conversations ?? 0,
                    $customer->email_verified_at ? 'Sí' : 'No',
                    $customer->banned_at ? 'Sí' : 'No',
                    self::csvSafe($this->channelList($customer)),
                ];

                if ($includeHealth) {
                    $row[] = $healthScores[$customer->id] ?? '';
                    // lifetimeOrders() no es batch (busca por email en
                    // Remarketing): N+1 acotado a 500 por chunk.
                    $row[] = number_format($this->aggregator->lifetimeOrders($customer)['totalSpent'], 2, ',', '.');
                }

                if ($includeExternal) {
                    $row[] = self::csvSafe($customer->externalIdFor('erp') ?? '');
                    $row[] = self::csvSafe($customer->externalIdFor('prestashop') ?? '');
                }

                fputcsv($handle, $row, ',', '"', '\\');
            }
        });
    }

    /**
     * Neutraliza la inyección de fórmulas en CSV (= + - @ tab CR).
     */
    public static function csvSafe(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value;
    }

    private function channelList(Customer $customer): string
    {
        return implode(', ', array_keys(array_filter([
            'email' => (bool) $customer->email,
            'whatsapp' => (bool) $customer->whatsapp_phone,
            'facebook' => (bool) $customer->facebook_psid,
            'instagram' => (bool) $customer->instagram_id,
        ])));
    }
}
