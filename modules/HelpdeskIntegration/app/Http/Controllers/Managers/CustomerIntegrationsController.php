<?php

namespace Modules\HelpdeskIntegration\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Services\AuthRateLimiter;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskIntegration\Http\Requests\Managers\DetailCustomerIntegrationRequest;
use Modules\HelpdeskIntegration\Http\Requests\Managers\LinkCustomerIntegrationRequest;
use Modules\HelpdeskIntegration\Http\Requests\Managers\RequestIdentityCodeRequest;
use Modules\HelpdeskIntegration\Http\Requests\Managers\SearchCustomerIntegrationRequest;
use Modules\HelpdeskIntegration\Http\Requests\Managers\UnlinkCustomerIntegrationRequest;
use Modules\HelpdeskIntegration\Http\Requests\Managers\VerifyIdentityCodeRequest;
use Modules\HelpdeskIntegration\Http\Requests\Managers\VerifyManualIdentityRequest;
use Modules\HelpdeskIntegration\Models\IntegrationAuditLog;
use Modules\HelpdeskIntegration\Services\CustomerIdentityVerificationService;
use Modules\HelpdeskIntegration\Services\CustomerIntegrationService;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CustomerIntegrationsController extends Controller
{
    private const IDENTITY_RATE_LIMIT_SCOPE = 'customer_identity';

    private const IDENTITY_REQUEST_RATE_LIMIT_SCOPE = 'customer_identity_request';

    public function __construct(
        private readonly CustomerIntegrationService $integrations,
        private readonly CustomerIdentityVerificationService $identity,
        private readonly AuthRateLimiter $limiter,
    ) {}

    /**
     * Antes de verificar identidad solo se expone informacion de contacto
     * basica; los datos de integraciones (IDs externos, estado de conexion)
     * quedan ocultos hasta pasar el gate.
     */
    public function show(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $verified = $this->identity->isVerified($customer);

        $payload = [
            'success' => true,
            'customer' => [
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone ?: $customer->whatsapp_phone,
            ],
            'identity' => $this->identityPayload($customer, $verified),
            'integrations' => [],
            // Catalogo de plataformas (label/icono/tipos de busqueda) — no es
            // dato del cliente, así que se expone también sin verificar: lo usa
            // el buscador de PrestaShop/ERP del modal de identidad (solo
            // consulta, no vincula) para ayudar a confirmar quién es el cliente.
            'linkable_platforms' => $this->integrations->linkablePlatforms(),
        ];

        if ($verified) {
            $payload = [...$payload, ...$this->integrations->buildPayload($customer)];
        }

        return response()->json($payload);
    }

    public function requestIdentity(RequestIdentityCodeRequest $request, Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $scopeId = (string) $customer->id;
        $check = $this->limiter->check(self::IDENTITY_REQUEST_RATE_LIMIT_SCOPE, $scopeId, $request);

        if (! $check['allowed']) {
            return response()->json([
                'success' => false,
                'message' => __('helpdeskintegration::messages.identity.too_many_codes', ['seconds' => $check['seconds']]),
                'locked_seconds' => $check['seconds'],
            ], 429);
        }

        $channel = $request->validated('channel');
        $destination = $channel === 'sms' ? ($customer->whatsapp_phone ?: $customer->phone) : $customer->email;

        if (! $destination) {
            return response()->json([
                'success' => false,
                'message' => $channel === 'sms'
                    ? __('helpdeskintegration::messages.identity.no_phone')
                    : __('helpdeskintegration::messages.identity.no_email'),
            ], 422);
        }

        $this->limiter->hit(self::IDENTITY_REQUEST_RATE_LIMIT_SCOPE, $scopeId, $request);
        $this->identity->requestCode($customer, $channel);

        return response()->json(['success' => true, 'message' => __('helpdeskintegration::messages.identity.code_sent')]);
    }

    public function verifyIdentity(VerifyIdentityCodeRequest $request, Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $scopeId = (string) $customer->id;
        $check = $this->limiter->check(self::IDENTITY_RATE_LIMIT_SCOPE, $scopeId, $request);

        if (! $check['allowed']) {
            return response()->json([
                'success' => false,
                'message' => __('helpdeskintegration::messages.identity.too_many_attempts', ['seconds' => $check['seconds']]),
                'locked_seconds' => $check['seconds'],
            ], 429);
        }

        if (! $this->identity->confirmCode($customer, $request->validated('code'), $request->validated('conversation_id'))) {
            $this->limiter->hit(self::IDENTITY_RATE_LIMIT_SCOPE, $scopeId, $request);

            $maxAttempts = (int) config('auth.auth-policy.rate_limits.'.self::IDENTITY_RATE_LIMIT_SCOPE.'.max_attempts', 3);
            $attemptsMade = $this->limiter->attempts(self::IDENTITY_RATE_LIMIT_SCOPE, $scopeId, $request);

            // Se registra una sola vez, justo cuando el intento agota el
            // cupo (las siguientes peticiones ya cortan antes en check()),
            // para no llenar el log con intentos repetidos mientras dure
            // el bloqueo.
            if ($attemptsMade >= $maxAttempts) {
                $this->logIdentityAudit($customer, 'identity_locked');
            }

            return response()->json([
                'success' => false,
                'message' => __('helpdeskintegration::messages.identity.invalid_code'),
                'attempts_made' => $attemptsMade,
                'max_attempts' => $maxAttempts,
            ], 422);
        }

        $this->limiter->clear(self::IDENTITY_RATE_LIMIT_SCOPE, $scopeId, $request);

        $identityPayload = $this->identityPayload($customer, true);
        $this->logIdentityAudit($customer, 'identity_verified', $identityPayload['channel'] ?? null);

        return response()->json([
            'success' => true,
            'identity' => $identityPayload,
            ...$this->integrations->buildPayload($customer),
        ]);
    }

    /**
     * El agente confirma la identidad sin enviar ningun codigo — mismo rate
     * limiter que verifyIdentity() para no dejar un atajo sin proteger.
     */
    public function verifyManual(VerifyManualIdentityRequest $request, Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $scopeId = (string) $customer->id;
        $check = $this->limiter->check(self::IDENTITY_RATE_LIMIT_SCOPE, $scopeId, $request);

        if (! $check['allowed']) {
            return response()->json([
                'success' => false,
                'message' => __('helpdeskintegration::messages.identity.too_many_attempts', ['seconds' => $check['seconds']]),
                'locked_seconds' => $check['seconds'],
            ], 429);
        }

        $this->identity->verifyManually($customer, $request->validated('conversation_id'), (int) auth()->id());
        $this->limiter->clear(self::IDENTITY_RATE_LIMIT_SCOPE, $scopeId, $request);

        $identityPayload = $this->identityPayload($customer, true);
        $this->logIdentityAudit($customer, 'identity_verified', 'manual');

        return response()->json([
            'success' => true,
            'identity' => $identityPayload,
            ...$this->integrations->buildPayload($customer),
        ]);
    }

    /**
     * Historial completo de auditoria del cliente (vincular/desvincular/
     * sincronizar/verificar identidad) — la vista principal solo muestra la
     * ultima actividad, este endpoint alimenta el "Ver historial completo".
     */
    public function auditLog(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);
        $this->assertIdentityVerified($customer);

        return response()->json([
            'success' => true,
            'entries' => $this->integrations->auditHistory($customer),
        ]);
    }

    /**
     * Reverifica todas las plataformas ya conectadas (no descubre nuevas —
     * para eso esta el buscador unificado en "Vincular plataforma").
     */
    public function sync(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);
        $this->assertIdentityVerified($customer);

        $result = $this->integrations->sync($customer);

        return response()->json([
            'success' => true,
            'message' => $result['linked']
                ? __('helpdeskintegration::messages.sync.success')
                : __('helpdeskintegration::messages.sync.empty'),
            'linked' => $result['linked'],
            ...$result['payload'],
        ]);
    }

    public function syncPlatform(Customer $customer, string $platform): JsonResponse
    {
        $this->authorize('view', $customer);
        $this->assertIdentityVerified($customer);

        $payload = $this->integrations->syncPlatform($customer, $platform);

        if ($payload === null) {
            return response()->json([
                'success' => false,
                'message' => __('helpdeskintegration::messages.sync.not_linked'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => __('helpdeskintegration::messages.sync.success'),
            ...$payload,
        ]);
    }

    /**
     * Solo consulta la plataforma remota — no vincula nada, así que se
     * permite antes de verificar identidad (a diferencia de link()/unlink()).
     */
    public function search(SearchCustomerIntegrationRequest $request, Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $data = $request->validated();

        $search = $this->integrations->search(
            $data['platform'],
            trim((string) ($data['q'] ?? '')),
            $data['type'] ?? 'email',
        );

        // platform_error=true: la plataforma remota falló o no respondió —
        // el modal lo distingue de una búsqueda legítimamente sin resultados.
        return response()->json([
            'success' => true,
            'results' => $search['results'],
            'platform_error' => ! $search['ok'],
        ]);
    }

    /**
     * Ficha completa de una plataforma ya vinculada (widget del panel
     * derecho) — mismo criterio de identidad que search(): solo consulta.
     */
    public function detail(DetailCustomerIntegrationRequest $request, Customer $customer): JsonResponse
    {
        $platform = $request->validated('platform');

        return response()->json([
            'success' => true,
            ...$this->integrations->detail($customer, $platform),
        ]);
    }

    public function link(LinkCustomerIntegrationRequest $request, Customer $customer): JsonResponse
    {
        $data = $request->validated();

        $payload = $this->integrations->link($customer, $data['platform'], trim($data['external_id']));

        return response()->json([
            'success' => true,
            'message' => __('helpdeskintegration::messages.link.success'),
            ...$payload,
        ]);
    }

    public function unlink(UnlinkCustomerIntegrationRequest $request, Customer $customer): JsonResponse
    {
        $data = $request->validated();

        $payload = $this->integrations->unlink($customer, $data['platform']);

        return response()->json([
            'success' => true,
            'message' => __('helpdeskintegration::messages.unlink.success'),
            ...$payload,
        ]);
    }

    private function assertIdentityVerified(Customer $customer): void
    {
        if (! $this->identity->isVerified($customer)) {
            throw new HttpException(403, __('helpdeskintegration::messages.identity.not_verified'));
        }
    }

    /**
     * A diferencia de helpdesk_customer_identity_verifications (purgada a
     * los 30 dias), esta traza queda en el log de auditoria con retencion
     * larga (180 dias) para poder responder "quien valido a este cliente y
     * cuando" mucho despues de que la sesion de verificacion haya caducado.
     */
    private function logIdentityAudit(Customer $customer, string $action, ?string $channel = null): void
    {
        IntegrationAuditLog::query()->create([
            'customer_id' => $customer->id,
            'platform' => 'identity',
            'action' => $action,
            'external_id' => $channel,
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * @return array{verified: bool, sms_enabled: bool, channel?: string, verified_at?: string, verified_by?: ?string, expires_at?: string}
     */
    private function identityPayload(Customer $customer, bool $verified): array
    {
        $payload = [
            'verified' => $verified,
            'sms_enabled' => helpdesk_integration_identity_sms_enabled(),
        ];

        if ($verified) {
            $payload = [...$payload, ...($this->identity->summary($customer) ?? [])];
        }

        return $payload;
    }
}
