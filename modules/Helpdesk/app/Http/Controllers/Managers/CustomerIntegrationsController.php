<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\CustomerCommerceSyncService;
use Modules\HelpdeskErp\Services\ErpContextService;

/**
 * Devuelve y sincroniza las integraciones externas de un cliente del helpdesk
 * (PrestaShop, gestión/ERP, etc.) a partir de sus `external_ids` y los datos
 * de plataforma detectados en sus atributos personalizados.
 */
class CustomerIntegrationsController extends Controller
{
    /**
     * Plataformas que el usuario puede vincular/desvincular manualmente.
     *
     * @var list<string>
     */
    private const LINKABLE_PLATFORMS = ['prestashop', 'erp'];

    /**
     * Catálogo de plataformas conocidas y su presentación en el modal.
     *
     * @var array<string, array{label: string}>
     */
    private const PLATFORM_LABELS = [
        'prestashop' => ['label' => 'PrestaShop'],
        'erp' => ['label' => 'Gestión (ERP)'],
        'shopify' => ['label' => 'Shopify'],
        'woocommerce' => ['label' => 'WooCommerce'],
        'magento' => ['label' => 'Magento'],
        'bigcommerce' => ['label' => 'BigCommerce'],
    ];

    public function show(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $customer->loadMissing('externalIds');

        return response()->json([
            'success' => true,
            'customer' => [
                'name' => $customer->name,
                'email' => $customer->email,
            ],
            'integrations' => $this->buildIntegrations($customer),
        ]);
    }

    public function sync(Customer $customer, CustomerCommerceSyncService $syncService): JsonResponse
    {
        $this->authorize('view', $customer);

        if (! $customer->email) {
            return response()->json([
                'success' => false,
                'message' => 'El cliente no tiene email para sincronizar.',
            ], 422);
        }

        $result = $syncService->sync($customer, force: true);

        $customer->load('externalIds');

        return response()->json([
            'success' => true,
            'message' => $result['linked']
                ? 'Integraciones sincronizadas correctamente.'
                : 'No se encontraron integraciones para este cliente.',
            'linked' => $result['linked'],
            'integrations' => $this->buildIntegrations($customer),
        ]);
    }

    /**
     * Busca clientes en PrestaShop o ERP para vinculación manual.
     * GET /customers/{customer}/integrations/search?platform=prestashop&q=garcia&type=name
     *
     * @return list<array{id: string, name: string, email: string, meta: string}>
     */
    public function search(Request $request, Customer $customer, CustomerCommerceSyncService $syncService): JsonResponse
    {
        $this->authorize('view', $customer);

        $platform = $request->query('platform', 'prestashop');
        $q = trim((string) $request->query('q', ''));
        $type = $request->query('type', 'email');

        if (strlen($q) < 2) {
            return response()->json(['success' => true, 'results' => []]);
        }

        $results = match ($platform) {
            'erp' => $this->searchErp($q, $type),
            default => $syncService->searchCustomers($q, $type),
        };

        return response()->json(['success' => true, 'results' => $results]);
    }

    /**
     * Vincula manualmente el cliente con una plataforma externa.
     * POST /customers/{customer}/integrations/link
     * Body: { platform, external_id }
     */
    public function link(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $platform = $request->input('platform', '');
        $externalId = trim((string) $request->input('external_id', ''));

        if (! in_array($platform, self::LINKABLE_PLATFORMS, true) || $externalId === '') {
            return response()->json(['success' => false, 'message' => 'Parámetros inválidos.'], 422);
        }

        $customer->linkExternalId($platform, $externalId, ['linked_via' => 'manual']);

        $customer->load('externalIds');

        return response()->json([
            'success' => true,
            'message' => 'Integración vinculada correctamente.',
            'integrations' => $this->buildIntegrations($customer),
        ]);
    }

    /**
     * Elimina el vínculo del cliente con una plataforma.
     * POST /customers/{customer}/integrations/unlink
     * Body: { platform }
     */
    public function unlink(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $platform = $request->input('platform', '');

        if (! in_array($platform, self::LINKABLE_PLATFORMS, true)) {
            return response()->json(['success' => false, 'message' => 'Plataforma no válida.'], 422);
        }

        $customer->externalIds()->where('platform', $platform)->delete();

        $customer->load('externalIds');

        return response()->json([
            'success' => true,
            'message' => 'Integración desvinculada.',
            'integrations' => $this->buildIntegrations($customer),
        ]);
    }

    /**
     * Busca en ERP via ErpContextService (manager Oracle).
     * Normaliza la respuesta del manager al mismo formato que PS.
     *
     * @return list<array{id: string, name: string, email: string, meta: string}>
     */
    private function searchErp(string $q, string $type): array
    {
        if (! class_exists(ErpContextService::class)) {
            return [];
        }

        $erpType = match ($type) {
            'id' => 'nif',
            'phone' => 'phone',
            default => 'email',
        };

        try {
            $results = app(ErpContextService::class)->searchCustomers($q, $erpType);
        } catch (\Throwable) {
            return [];
        }

        return array_map(fn ($r) => [
            'id' => (string) ($r['id'] ?? ''),
            'name' => trim(($r['label'] ?? '').' '.($r['surnames'] ?? '')),
            'email' => $r['email'] ?? '',
            'meta' => 'ERP-'.($r['id'] ?? ''),
        ], array_filter($results, fn ($r) => ! empty($r['id'])));
    }

    /**
     * Construye la lista de integraciones a partir de los external_ids
     * guardados y de la plataforma detectada en custom_attributes. PrestaShop
     * y ERP se muestran siempre (conectado o no) por ser las integraciones core.
     *
     * @return list<array{platform: string, label: string, connected: bool, external_id: ?string}>
     */
    private function buildIntegrations(Customer $customer): array
    {
        $linked = $customer->externalIds
            ->keyBy('platform')
            ->map(fn ($link) => $link->external_id);

        $platforms = ['prestashop', 'erp'];

        $detected = $customer->custom_attributes['platform'] ?? null;
        if ($detected && isset(self::PLATFORM_LABELS[$detected]) && ! in_array($detected, $platforms, true)) {
            $platforms[] = $detected;
        }

        foreach ($linked->keys() as $platform) {
            if (! in_array($platform, $platforms, true)) {
                $platforms[] = $platform;
            }
        }

        return array_map(function (string $platform) use ($linked) {
            $externalId = $linked->get($platform);

            return [
                'platform' => $platform,
                'label' => self::PLATFORM_LABELS[$platform]['label'] ?? ucfirst($platform),
                'connected' => $externalId !== null,
                'external_id' => $externalId !== null ? (string) $externalId : null,
            ];
        }, $platforms);
    }
}
