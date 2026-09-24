<?php

namespace Modules\HelpdeskContacts\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskIntegration\Services\CustomerIdentityVerificationService;
use Modules\HelpdeskIntegration\Services\CustomerIntegrationService;

/**
 * Modal "Vínculos e identidad" de la ficha 360: plataformas vinculadas,
 * historial de vinculaciones, desvincular, sugerencias cruzadas ERP ↔
 * PrestaShop (por el código de internet de Gestión) y el estado de la
 * verificación de identidad (el envío/confirmación del código lo hacen las
 * rutas de HelpdeskIntegration, con sus propios límites).
 *
 * Vincular sigue siendo contacts.external-link (sin OTP, como hasta ahora);
 * desvincular exige además helpdesk.integrations.manage, el mismo permiso
 * dedicado que pide HelpdeskIntegration para tocar vínculos.
 */
class ContactLinksController extends Controller
{
    public function show(Customer $customer, Request $request): JsonResponse
    {
        $this->assertAvailable($customer, $request);

        return response()->json(['success' => true, 'data' => $this->payload($customer, $request)]);
    }

    public function unlink(Customer $customer, Request $request): JsonResponse
    {
        $this->assertAvailable($customer, $request);
        abort_unless($this->canUnlink($request), 403, 'No tienes permiso para desvincular plataformas.');

        $customer->load('externalIds');
        $linked = $customer->externalIds->pluck('platform')->all();

        $data = $request->validate([
            'platform' => ['required', 'string', Rule::in($linked)],
        ], [
            'platform.in' => 'Esa plataforma no está vinculada a este contacto.',
        ]);

        app(CustomerIntegrationService::class)->unlink($customer, $data['platform']);

        return response()->json([
            'success' => true,
            'message' => 'Plataforma desvinculada.',
            'data' => $this->payload($customer->fresh('externalIds'), $request),
        ]);
    }

    /**
     * Gestión guarda en cada cliente su "código de internet", que es el id
     * de su cuenta en PrestaShop: con una de las dos vinculada se puede
     * proponer la otra. Consulta remota (lenta): se pide aparte al abrir el
     * modal, nunca al cargar la ficha.
     */
    public function suggestions(Customer $customer, Request $request): JsonResponse
    {
        $this->assertAvailable($customer, $request);

        $customer->load('externalIds');
        $service = app(CustomerIntegrationService::class);
        $psId = $customer->externalIdFor('prestashop');
        $erpId = $customer->externalIdFor('erp');
        $suggestions = [];

        try {
            if ($psId !== null && $erpId === null) {
                $found = $service->search('erp', (string) $psId, 'auto');
                foreach ($found['results'] ?? [] as $row) {
                    if ((string) ($row['code_internet'] ?? '') === (string) $psId && ($row['id'] ?? '') !== '') {
                        $suggestions[] = [
                            'platform' => 'erp',
                            'platformLabel' => 'Gestión (ERP)',
                            'externalId' => (string) $row['id'],
                            'name' => $row['name'] ?? '',
                            'detail' => trim('ERP-'.$row['id'].' · '.($row['email'] ?? ''), ' ·'),
                            'reason' => 'Su ficha de Gestión apunta a la cuenta '.$psId.' de la tienda, que ya está vinculada.',
                        ];
                    }
                }
            }

            if ($erpId !== null && $psId === null) {
                $detail = $service->detail($customer, 'erp');
                $code = (string) ($detail['record']['code_internet'] ?? '');
                if ($code !== '' && ctype_digit($code) && (int) $code > 0) {
                    $suggestions[] = [
                        'platform' => 'prestashop',
                        'platformLabel' => 'PrestaShop',
                        'externalId' => $code,
                        'name' => $detail['record']['name'] ?? '',
                        'detail' => 'Cuenta '.$code.' de la tienda',
                        'reason' => 'Es el código de internet de su ficha de Gestión (ERP-'.$erpId.').',
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::info('HelpdeskContacts: sugerencias de vínculo no disponibles', ['customer_id' => $customer->id, 'error' => $e->getMessage()]);

            return response()->json(['success' => true, 'data' => [], 'unavailable' => true]);
        }

        return response()->json(['success' => true, 'data' => $suggestions]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Customer $customer, Request $request): array
    {
        $customer->loadMissing('externalIds');
        $service = app(CustomerIntegrationService::class);
        $identity = app(CustomerIdentityVerificationService::class);
        $user = $request->user();

        $integrations = collect($service->buildPayload($customer)['integrations'] ?? [])
            ->filter(fn (array $i) => ! empty($i['connected']))
            ->map(fn (array $i) => [
                'platform' => $i['platform'],
                'label' => $i['label'] ?? ucfirst($i['platform']),
                'externalId' => $i['external_id'] ?? null,
                'syncStatus' => $i['sync_status'] ?? null,
                'lastSyncedAt' => $i['last_synced_at'] ?? null,
            ])
            ->values()
            ->all();

        $routes = [
            'request' => 'manager.helpdesk.customers.identity.request',
            'verify' => 'manager.helpdesk.customers.identity.verify',
            'verifyManual' => 'manager.helpdesk.customers.identity.verify-manual',
        ];

        return [
            'integrations' => $integrations,
            'history' => $service->auditHistory($customer, 20),
            'identity' => [
                'verified' => $identity->isVerified($customer),
                'summary' => $identity->summary($customer),
                'smsEnabled' => function_exists('helpdesk_integration_identity_sms_enabled') && helpdesk_integration_identity_sms_enabled(),
                'hasEmail' => filled($customer->email),
                'hasPhone' => filled($customer->whatsapp_phone ?: $customer->phone),
                // Las rutas de identidad de HelpdeskIntegration van tras
                // can:helpdesk.view: sin ese permiso los botones darían 403.
                'urls' => collect($user?->can('helpdesk.view') ? $routes : [])
                    ->filter(fn (string $name) => Route::has($name))
                    ->map(fn (string $name) => route($name, $customer))
                    ->all(),
            ],
            'can' => [
                'unlink' => $this->canUnlink($request),
                'link' => (bool) $user?->can('contacts.update'),
                'verifyManual' => (bool) $user?->can('helpdesk.integrations.manage'),
            ],
        ];
    }

    private function canUnlink(Request $request): bool
    {
        $user = $request->user();

        return (bool) ($user?->can('contacts.update') && $user->can('helpdesk.integrations.manage'));
    }

    private function assertAvailable(Customer $customer, Request $request): void
    {
        abort_unless(
            helpdesk_integration_enabled() && class_exists(CustomerIntegrationService::class),
            404
        );

        abort_unless(
            Customer::query()->whereKey($customer->getKey())->forAgent($request->user())->exists(),
            403,
            'Sin autorización sobre este contacto.'
        );
    }
}
